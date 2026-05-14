# ALB Security Group - allow HTTP from internet
resource "aws_security_group" "alb" {
  name   = "notely-alb-sg"
  vpc_id = aws_vpc.main.id
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
  tags = { Name = "notely-alb-sg" }
}
# ECS Security Group - only accept traffic from ALB
# resource "aws_security_group" "ecs" {
#   name   = "notely-ecs-sg"
#   vpc_id = aws_vpc.main.id
#   ingress { 
#     from_port = 80
#    to_port = 80
#     protocol = "tcp"
#      security_groups = [aws_security_group.alb.id]
#       }
#   egress  {
#      from_port = 0
#     to_port = 0 
#      protocol = "-1"
#       cidr_blocks = ["0.0.0.0/0"] 
#       }
# }
resource "aws_security_group" "ecs" {
  name   = "notely-ecs-sg"
  vpc_id = aws_vpc.main.id

  ingress {
    from_port       = 32768
    to_port         = 65535
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = { Name = "notely-ecs-sg" }
}
# RDS Security Group - only accept traffic from ECS
resource "aws_security_group" "rds" {
  name   = "notely-rds-sg"
  vpc_id = aws_vpc.main.id
  ingress {
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = [aws_security_group.ecs.id]
  }
}

resource "aws_security_group_rule" "ecs_self_ingress" {
  type              = "ingress"
  from_port         = 0
  to_port           = 65535
  protocol          = "-1" # All protocols
  security_group_id = aws_security_group.ecs.id
  self              = true
}