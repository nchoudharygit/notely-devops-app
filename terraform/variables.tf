variable "environment" {
  description = "The environment to deploy to"
  type        = string
  default     = "staging"
}

variable "aws_region" {
  description = "The AWS region to deploy to"
  type        = string
  default     = "ap-south-1"
}

variable "db_password" {
  description = "The password for the database"
  type        = string
  sensitive   = true
}

variable "app_image_tag" {
  description = "The tag for the application image"
  type        = string
  default     = "latest"
}

variable "grafana_password" {
  description = "The password for Grafana"
  type        = string
  sensitive   = true
}