resource "aws_ecs_task_definition" "prometheus" {
  family = "prometheus-${var.environment}"
  network_mode       = "bridge"
  execution_role_arn = aws_iam_role.ecs_execution.arn
  container_definitions = jsonencode([{
    name = "prometheus", image = "prom/prometheus:latest"
    memory = 256, cpu = 128
    portMappings = [{ containerPort = 9090, hostPort = 9090 }]
    logConfiguration = { logDriver = "awslogs", options = {
      awslogs-group = "/ecs/prometheus-${var.environment}",
      awslogs-region = var.aws_region, awslogs-stream-prefix = "prometheus" } }
  }])
}
resource "aws_ecs_service" "prometheus" {
  name = "prometheus-${var.environment}"
  cluster = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.prometheus.arn
  desired_count = 1
}
resource "aws_ecs_task_definition" "grafana" {
  family = "grafana-${var.environment}"
  network_mode = "bridge"
  execution_role_arn = aws_iam_role.ecs_execution.arn
  container_definitions = jsonencode([{
    name = "grafana", image = "grafana/grafana:latest"
    memory = 256, cpu = 128
    portMappings = [{ containerPort = 3000, hostPort = 3000 }]
    environment = [
      { name = "GF_SECURITY_ADMIN_PASSWORD", value = local.grafana_password },
      { name = "GF_USERS_ALLOW_SIGN_UP", value = "false" }
    ]
  }])
}
resource "aws_ecs_service" "grafana" {
  name = "grafana-${var.environment}"
  cluster = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.grafana.arn
  desired_count = 1
}