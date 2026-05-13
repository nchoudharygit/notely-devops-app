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