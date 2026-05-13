# Reference existing secrets (created manually above)
data "aws_secretsmanager_secret" "db_password" {
  name = "notely/staging/db-password"
}
data "aws_secretsmanager_secret_version" "db_password" {
  secret_id = data.aws_secretsmanager_secret.db_password.id
}
data "aws_secretsmanager_secret" "grafana_password" {
  name = "notely/staging/grafana-password"
}
data "aws_secretsmanager_secret_version" "grafana_password" {
  secret_id = data.aws_secretsmanager_secret.grafana_password.id
}
# Convenience locals
locals {
  db_password      = data.aws_secretsmanager_secret_version.db_password.secret_string
  grafana_password = data.aws_secretsmanager_secret_version.grafana_password.secret_string
}