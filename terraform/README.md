## First Time Setup

Before running terraform init, create remote state infrastructure:

```bash
chmod +x scripts/setup-remote-state.sh
./scripts/setup-remote-state.sh
```

Then create `backend.tf` (not committed to git):

```hcl
terraform {
  backend "s3" {
    bucket         = "notely-terraform-state-<YOUR_ACCOUNT_ID>"
    key            = "notely/terraform.tfstate"
    region         = "ap-south-1"
    dynamodb_table = "notely-terraform-locks"
    encrypt        = true
  }
}
```

Then run:
```bash
terraform init
```

## To configure backend.tf via command

cd terraform
ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
terraform init \
  -backend-config="bucket=notely-terraform-state-${ACCOUNT_ID}" \
  -backend-config="key=notely/terraform.tfstate" \
  -backend-config="region=ap-south-1" \
  -backend-config="dynamodb_table=notely-terraform-locks" \
  -backend-config="encrypt=true"