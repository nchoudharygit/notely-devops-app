resource "aws_iam_role" "ecs_execution" {
  name = "notely-ecs-execution-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{ Action = "sts:AssumeRole", Effect = "Allow",
    Principal = { Service = "ecs-tasks.amazonaws.com" } }]
  })
}
resource "aws_iam_role_policy_attachment" "ecs_execution" {
  role       = aws_iam_role.ecs_execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}
# Allow ECS to read from Secrets Manager
resource "aws_iam_role_policy" "ecs_secrets" {
  name = "ecs-secrets-policy"
  role = aws_iam_role.ecs_execution.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{ Effect = "Allow",
      Action = ["secretsmanager:GetSecretValue"],
    Resource = ["arn:aws:secretsmanager:${var.aws_region}:*:secret:notely/*"] }]
  })
}
resource "aws_iam_role" "ecs_node" {
  name = "notely-ecs-node-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{ Action = "sts:AssumeRole", Effect = "Allow",
    Principal = { Service = "ec2.amazonaws.com" } }]
  })
}
resource "aws_iam_role_policy_attachment" "ecs_node" {
  role       = aws_iam_role.ecs_node.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceforEC2Role"
}
resource "aws_iam_instance_profile" "ecs_node" {
  name = "notely-ecs-node-profile"
  role = aws_iam_role.ecs_node.name
}
resource "aws_cloudwatch_log_group" "notely" {
  name              = "/ecs/notely-${var.environment}"
  retention_in_days = 7
}