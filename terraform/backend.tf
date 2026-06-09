terraform {
  backend "s3" {
    bucket       = "notely-terraform-state-162185499985"
    key          = "staging/terraform.tfstate"
    region       = "ap-south-1"
    use_lockfile = true
    encrypt      = true
  }
}