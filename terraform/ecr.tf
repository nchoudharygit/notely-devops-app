resource "aws_ecr_repository" "notely-app-ecr" {
  name  = "notely-app"
  image_tag_mutability = "MUTABLE"

    tags = {
        Environment = var.environment
        Project     = "notely-app"
    }
    
    image_scanning_configuration {
      scan_on_push = true
    }
}

resource "aws_ecr_lifecycle_policy" "notely-app-lifecycle" {
    repository = aws_ecr_repository.notely-app-ecr.name
    policy = jsonencode({
        rules = [
            {
                rulePriority = 1
                description  = "Expire untagged images older than 30 days"
                selection    = {
                    tagStatus    = "untagged"
                    countType    = "sinceImagePushed"
                    countUnit    = "days"
                    countNumber  = 30
                }
                action       = {
                    type = "expire"
                }
            }
        ]
    })
  
}

output ecr_url {
    description = "The URL of the ECR repository"
    value = aws_ecr_repository.notely-app-ecr.name
}