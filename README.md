
# Notely — Production-Grade 3-Tier PHP App on AWS ECS

> A fully containerised, infrastructure-as-code deployment of a PHP notes application on AWS — built to demonstrate real-world DevOps practices: Terraform-provisioned networking, multi-container ECS tasks, secrets management, and automated CI/CD via GitHub Actions.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Infrastructure Components](#infrastructure-components)
- [Repository Structure](#repository-structure)
- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [CI/CD Pipeline](#cicd-pipeline)
- [Environment Variables & Secrets](#environment-variables--secrets)
- [Terraform State Management](#terraform-state-management)
- [How to Destroy](#how-to-destroy)
- [Key Design Decisions](#key-design-decisions)
- [Lessons Learned](#lessons-learned)

---

## Architecture Overview

```
Internet
    │
    ▼
[ALB — Application Load Balancer]  ← Public Subnet
    │
    ▼
[ECS Fargate Task]                 ← Private Subnet
  ├── Container 1: Nginx (reverse proxy)
  └── Container 2: PHP-FPM (application)
    │
    ├──► [RDS PostgreSQL]          ← Private Subnet (no internet access)
    │
    └──► [Secrets Manager]         ← via VPC Endpoint (PrivateLink)
         [ECR]                     ← via VPC Endpoint (PrivateLink)
         [CloudWatch Logs]         ← via VPC Endpoint (PrivateLink)
```

**Traffic flow:**
1. User hits the ALB (public subnet, port 80/443)
2. ALB forwards to Nginx container in ECS task (private subnet)
3. Nginx proxies PHP requests to PHP-FPM on port 9000
4. PHP-FPM connects to RDS PostgreSQL (private subnet, no public access)
5. Secrets (DB credentials) are fetched from Secrets Manager at task startup — never stored in env files or image layers

---

## Infrastructure Components

| Component | Service | Details |
|---|---|---|
| Networking | AWS VPC | Custom VPC, 2 public + 2 private subnets across 2 AZs |
| Load Balancing | ALB | Public-facing, routes HTTP to ECS target group |
| Container Runtime | ECS Fargate | Serverless — no EC2 instances to manage |
| Application | ECS Task | Multi-container: Nginx + PHP-FPM |
| Database | RDS PostgreSQL | Private subnet, no public access, encrypted at rest |
| Secrets | Secrets Manager | DB credentials injected at runtime via VPC Endpoint |
| Image Registry | ECR | Private registry, images pulled via VPC Endpoint (PrivateLink) |
| Logs | CloudWatch Logs | All container stdout/stderr shipped to log groups |
| IaC | Terraform | All infrastructure defined as code — no manual AWS Console steps |
| CI/CD | GitHub Actions | Build → Push to ECR → Deploy to ECS on every merge to `main` |

### Why VPC Endpoints (PrivateLink)?

ECS tasks run in **private subnets with no internet gateway**. Without VPC Endpoints, tasks cannot reach ECR (to pull images), Secrets Manager (to fetch credentials), or CloudWatch (to ship logs).

VPC Endpoints allow private subnet resources to communicate with AWS services **entirely within the AWS network** — no NAT Gateway, no internet exposure, lower cost.

---

## Repository Structure

```
notely-devops-app/
│
├── app/                        # PHP application source
│   ├── index.php
│   ├── config/
│   └── ...
│
├── docker/
│   ├── nginx/
│   │   ├── Dockerfile
│   │   └── nginx.conf          # Reverse proxy config → PHP-FPM:9000
│   └── php/
│       ├── Dockerfile
│       └── php.ini
│
├── terraform/
│   ├── main.tf                 # Root module — calls all child modules
│   ├── variables.tf
│   ├── outputs.tf
│   ├── backend.tf              # S3 backend + DynamoDB state locking
│   │
│   ├── modules/
│   │   ├── vpc/                # VPC, subnets, route tables, IGW
│   │   ├── alb/                # ALB, target group, listener
│   │   ├── ecs/                # Cluster, task definition, service
│   │   ├── rds/                # PostgreSQL instance, subnet group
│   │   ├── ecr/                # Container registry
│   │   ├── secrets/            # Secrets Manager secret + policy
│   │   ├── vpc_endpoints/      # PrivateLink endpoints (ECR, S3, SM, CW)
│   │   ├── iam/                # ECS task role + execution role
│   │   └── security_groups/    # SG rules for ALB, ECS, RDS
│
├── .github/
│   └── workflows/
│       └── deploy.yml          # CI/CD pipeline
│
├── docker-compose.yml          # Local development
└── README.md
```

---

## Prerequisites

- AWS CLI configured (`aws configure`)
- Terraform >= 1.5
- Docker
- An AWS account with sufficient IAM permissions

---

## Getting Started

### 1. Clone the repo

```bash
git clone https://github.com/nchoudharygit/notely-devops-app.git
cd notely-devops-app
```

### 2. Set up Terraform backend

Before running Terraform, create the S3 bucket and DynamoDB table for remote state manually (one-time setup):

```bash
# Create S3 bucket for state
aws s3api create-bucket \
  --bucket notely-terraform-state \
  --region ap-south-1 \
  --create-bucket-configuration LocationConstraint=ap-south-1

# Enable versioning
aws s3api put-bucket-versioning \
  --bucket notely-terraform-state \
  --versioning-configuration Status=Enabled

# Create DynamoDB table for state locking
aws dynamodb create-table \
  --table-name notely-terraform-lock \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST \
  --region ap-south-1
```

### 3. Provision infrastructure

```bash
cd terraform
terraform init
terraform plan
terraform apply
```

Terraform will output the ALB DNS name when complete.

### 4. Run locally (Docker Compose)

```bash
docker-compose up --build
```

App will be available at `http://localhost:8080`

---

## CI/CD Pipeline

**File:** `.github/workflows/deploy.yml`

```
Push to main branch
        │
        ▼
[1. Checkout code]
        │
        ▼
[2. Configure AWS credentials]   ← via GitHub Secrets
        │
        ▼
[3. Login to ECR]
        │
        ▼
[4. Build Docker images]
    ├── nginx:latest
    └── php-fpm:latest
        │
        ▼
[5. Push images to ECR]
        │
        ▼
[6. Update ECS service]          ← forces new deployment (rolling update)
        │
        ▼
[7. Wait for ECS to stabilise]   ← ecs wait services-stable
```

**ECS rolling update** ensures zero downtime — new task starts, health check passes, old task is stopped.

### GitHub Secrets required

| Secret | Description |
|---|---|
| `AWS_ACCESS_KEY_ID` | IAM user with ECS/ECR deploy permissions |
| `AWS_SECRET_ACCESS_KEY` | Corresponding secret |
| `AWS_REGION` | e.g. `ap-south-1` |
| `ECR_REGISTRY` | ECR registry URL |

---

## Environment Variables & Secrets

**DB credentials are never stored in `.env` files or baked into Docker images.**

Credentials are stored in **AWS Secrets Manager** and injected into the ECS task at runtime via the `secrets` block in the task definition:

```json
"secrets": [
  {
    "name": "DB_PASSWORD",
    "valueFrom": "arn:aws:secretsmanager:ap-south-1:ACCOUNT_ID:secret:notely/db-credentials:password::"
  },
  {
    "name": "DB_USER",
    "valueFrom": "arn:aws:secretsmanager:ap-south-1:ACCOUNT_ID:secret:notely/db-credentials:username::"
  }
]
```

The ECS task execution role has an IAM policy allowing `secretsmanager:GetSecretValue` only for this specific secret ARN.

---

## Terraform State Management

Remote state is stored in S3 with DynamoDB locking:

```hcl
# terraform/backend.tf
terraform {
  backend "s3" {
    bucket         = "notely-terraform-state"
    key            = "notely/terraform.tfstate"
    region         = "ap-south-1"
    dynamodb_table = "notely-terraform-lock"
    encrypt        = true
  }
}
```

- **S3** stores the state file (versioned — easy rollback)
- **DynamoDB** prevents concurrent `terraform apply` conflicts

---

## How to Destroy

```bash
cd terraform
terraform destroy
```

> **Note:** RDS has `deletion_protection = false` in this project for easy teardown. In a real production setup, this should be `true`.

---

## Key Design Decisions

**Why ECS Fargate over EC2?**
No EC2 instances to patch, scale, or manage. Fargate handles the underlying compute — you define the task, AWS runs it.

**Why Nginx + PHP-FPM as separate containers in one task?**
This mirrors real production PHP deployments. Nginx handles static files and HTTP termination; PHP-FPM handles application logic. Running them in the same ECS task ensures they share a network namespace, so Nginx can proxy to `localhost:9000`.

**Why PrivateLink instead of a NAT Gateway?**
NAT Gateways cost ~$0.045/hour plus data transfer fees. For AWS-internal traffic (ECR, Secrets Manager, CloudWatch), VPC Endpoints (PrivateLink) are cheaper and keep traffic entirely within the AWS network. This was a deliberate cost and security optimisation.

**Why Secrets Manager over SSM Parameter Store?**
Secrets Manager supports automatic rotation, which is the production-grade approach for database credentials. Parameter Store is fine for non-sensitive config; Secrets Manager is the right tool for credentials.

---

## Lessons Learned

- **VPC Endpoint configuration is non-trivial.** ECR actually requires *three* endpoints: `com.amazonaws.region.ecr.dkr`, `com.amazonaws.region.ecr.api`, and `com.amazonaws.region.s3` (for image layers). Missing any one of them causes image pull failures with misleading error messages.

- **ECS task role vs execution role** — easy to confuse. The *execution role* is what ECS uses to pull images and fetch secrets at startup. The *task role* is what your running application code uses to call AWS APIs. They need separate IAM policies.

- **Terraform `depends_on` for VPC Endpoints** — ECS service must explicitly depend on VPC endpoints being ready, otherwise the first deployment fails because the task can't reach ECR to pull the image.

- **Health check grace period** — RDS takes ~3–4 minutes to become available after `terraform apply`. If the ECS service starts before RDS is ready, the PHP app fails its health check and ECS keeps cycling tasks. Solution: `health_check_grace_period_seconds` on the ECS service + RDS `depends_on` in Terraform.

---

## Author

**Neha Choudhary**
[github.com/nchoudharygit](https://github.com/nchoudharygit) · [linkedin.com/in/neha-choudhary-75674224](https://linkedin.com/in/neha-choudhary-75674224)
