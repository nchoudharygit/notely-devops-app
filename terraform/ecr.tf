# This Terraform configuration defines two AWS ECR repositories: one for the Notely application and another for the Nginx server. It also includes lifecycle policies to manage image retention and outputs the repository URLs for both repositories.

# ECR repository for Notely application
resource "aws_ecr_repository" "notely" {
  name                 = "notely-app"
  image_tag_mutability = "MUTABLE"
  force_delete         = true # Caution: deletes all images when repo is deleted
  image_scanning_configuration { scan_on_push = true }
  tags = { Name = "notely-app", Environment = var.environment }
}
resource "aws_ecr_lifecycle_policy" "notely" {
  repository = aws_ecr_repository.notely.name
  policy = jsonencode({ rules = [{ rulePriority = 1, description = "Keep last 10 images",
    selection = { tagStatus = "any", countType = "imageCountMoreThan", countNumber = 10 },
  action = { type = "expire" } }] })
}
output "ecr_url" { value = aws_ecr_repository.notely.repository_url }

# ECR repository for Nginx server
resource "aws_ecr_repository" "nginx" {
  name                 = "notely-nginx"
  image_tag_mutability = "MUTABLE"
  force_delete         = true # Caution: deletes all images when repo is deleted
  image_scanning_configuration { scan_on_push = true }
  tags = { Name = "notely-nginx", Environment = var.environment }
}
output "nginx_ecr_url" { value = aws_ecr_repository.nginx.repository_url }

# To authenticate Docker to ECR, use the following command:
# aws ecr get-login-password --region <region> | docker login --username AWS --password-stdin <aws_account_id>.dkr.ecr.<region>.amazonaws.com