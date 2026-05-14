resource "aws_ecs_cluster" "main" {
  name = "notely-cluster-${var.environment}"
}

resource "aws_launch_template" "ecs" {
  name_prefix   = "notely-ecs-"
  image_id      = data.aws_ami.ecs_ami.id
  instance_type = "t3.micro"
  iam_instance_profile { arn = aws_iam_instance_profile.ecs_node.arn }
  vpc_security_group_ids = [aws_security_group.ecs.id]
  user_data              = base64encode("#!/bin/bash\necho ECS_CLUSTER=${aws_ecs_cluster.main.name} >> /etc/ecs/ecs.config")
}

resource "aws_autoscaling_group" "ecs" {
  desired_capacity    = 1
  max_size            = 2
  min_size            = 1
  vpc_zone_identifier = aws_subnet.public[*].id
  launch_template {
    id      = aws_launch_template.ecs.id
    version = "$Latest"
  }
}

resource "aws_ecs_task_definition" "notely" {
  family             = "notely-${var.environment}"
  network_mode       = "bridge"
  execution_role_arn = aws_iam_role.ecs_execution.arn
  container_definitions = jsonencode([
    {
      name         = "nginx"
      image = "162185499985.dkr.ecr.ap-south-1.amazonaws.com/notely-nginx:${var.app_image_tag}"
      portMappings = [{ containerPort = 80, hostPort = 0 }]
      essential    = true
      dependsOn    = [{ containerName = "php", condition = "START" }]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = "/ecs/notely-${var.environment}"
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "nginx"
        }
      }
      memory = 64
      cpu    = 64
    },
    {
      name         = "php"
      image        = "162185499985.dkr.ecr.ap-south-1.amazonaws.com/notely-app:${var.app_image_tag}"
      essential    = true
      portMappings = []
      environment = [
        { name = "DB_HOST", value = aws_db_instance.postgres.address },
        { name = "DB_NAME", value = "notely" },
        { name = "DB_USER", value = "notely_user" },
        { name = "DB_PASSWORD", value = local.db_password },
        { name = "APP_ENV", value = var.environment }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = "/ecs/notely-${var.environment}"
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "php"
        }
      }
      memory = 256
      cpu    = 128
    }
  ])
}

resource "aws_ecs_service" "notely" {
  name            = "notely-service-${var.environment}"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.notely.arn
  desired_count   = 1
  load_balancer {
    target_group_arn = aws_lb_target_group.notely.arn
    container_name   = "nginx"
    container_port   = 80
  }
  deployment_minimum_healthy_percent = 0
  deployment_maximum_percent         = 100
  depends_on                         = [aws_lb_listener.http]
  health_check_grace_period_seconds = 300
}

data "aws_ami" "ecs_ami" {
  most_recent = true
  owners      = ["amazon"]
  filter {
    name   = "name"
    values = ["amzn2-ami-ecs-hvm-*-x86_64-ebs"]
  }
}
