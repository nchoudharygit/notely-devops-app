resource "aws_db_subnet_group" "main" {
  name       = "notely-db-subnet-${var.environment}"
  subnet_ids = aws_subnet.private[*].id
}
resource "aws_db_instance" "postgres" {
  identifier              = "notely-db-${var.environment}"
  engine                  = "postgres"
  engine_version          = "15"
  instance_class          = "db.t3.micro" # FREE TIER
  allocated_storage       = 20            # FREE TIER - 20GB
  db_name                 = "notely"
  username                = "notely_user"
  password                = local.db_password # From Secrets Manager!
  db_subnet_group_name    = aws_db_subnet_group.main.name
  vpc_security_group_ids  = [aws_security_group.rds.id]
  skip_final_snapshot     = true
  deletion_protection     = false
  multi_az                = false
  publicly_accessible     = false
  backup_retention_period = 0
  tags                    = { Environment = var.environment }
}
output "db_endpoint" { value = aws_db_instance.postgres.address }