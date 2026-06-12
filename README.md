# 📝 Notely — Production-Grade DevOps Project

A full-stack note-taking application deployed on AWS using industry-standard DevOps practices — containerization, infrastructure as code, CI/CD automation, and production monitoring.

**Live URL:** http://notely-alb-staging-1814382760.ap-south-1.elb.amazonaws.com/

---

## 🏗️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Application | PHP + Nginx + PostgreSQL + Redis + MinIO |
| Containerization | Docker + Docker Compose |
| Container Registry | AWS ECR |
| Infrastructure | Terraform (IaC) |
| Cloud | AWS — VPC, ECS, RDS, ALB, ECR, CloudWatch |
| CI/CD | GitHub Actions |
| Monitoring | Amazon CloudWatch (Logs + Alarms) |

---

## 🏛️ Architecture

```
Internet
    │
    ▼
┌─────────────────────────────────┐
│   Application Load Balancer     │  ← AWS ALB (port 80)
└─────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────┐
│        ECS Cluster              │
│  ┌──────────┐  ┌──────────┐    │
│  │  Notely  │  │  Notely  │    │  ← 2 Tasks (High Availability)
│  │  Task 1  │  │  Task 2  │    │
│  └──────────┘  └──────────┘    │
└─────────────────────────────────┘
    │                │
    ▼                ▼
┌────────┐    ┌──────────────┐
│  RDS   │    │  AWS ECR     │
│Postgres│    │ Image Registry│
└────────┘    └──────────────┘

VPC: 10.0.0.0/16
├── Public Subnets  (10.0.1.0/24, 10.0.2.0/24)  ← ECS + ALB
└── Private Subnets (10.0.10.0/24, 10.0.11.0/24) ← RDS
```

---

## 📁 Project Structure

```
notely-devops-app/
├── app/
│   ├── backend/          # PHP application code
│   ├── frontend/         # HTML/CSS/JS
│   ├── nginx/            # Nginx configuration
│   └── specs/            # API specifications
├── terraform/            # AWS Infrastructure as Code
│   ├── provider.tf       # AWS provider config
│   ├── variables.tf      # Input variables
│   ├── ecr.tf            # ECR repository
│   ├── networking.tf     # VPC, Subnets, IGW, Route Tables
│   ├── security_groups.tf # Firewall rules
│   ├── rds.tf            # PostgreSQL database
│   ├── ecs.tf            # ECS Cluster, Task, Service
│   ├── alb.tf            # Application Load Balancer
│   └── iam.tf            # IAM Roles and Policies
├── .github/
│   └── workflows/
│       ├── ci.yml          # PR — build + test
│       ├── deploy.yml      # Build, push image to ECR, force ECS deployment
│       └── infra.yml       # Terraform infrastructure changes (separate from app deploy)
├── docker-compose.yml    # Local development
└── Dockerfile            # Container image definition
```

---

## 🚀 CI/CD Pipeline

```
Developer pushes code
        │
        ▼
┌──────────────────┐
│   Pull Request   │ → CI Pipeline triggers
│                  │   ✅ Docker build
│                  │   ✅ Lint + tests
└──────────────────┘
        │ merge to main
        ▼
┌──────────────────┐
│  Deploy Pipeline │ → Automatic
│                  │   ✅ Build + push new image to ECR
│                  │   ✅ ECS force-new-deployment (rolling update)
│                  │   ✅ Wait for service stability
└──────────────────┘

Infrastructure changes (VPC, ECS, RDS, ALB, etc.) are managed
separately via infra.yml using Terraform — decoupled from the
application deploy pipeline.
```

---

## 📊 Monitoring

Application logs and metrics are monitored via **Amazon CloudWatch**:

| Component | What's monitored |
|-----------|-------------------|
| ECS Task Logs | Application logs streamed to CloudWatch Log Groups (7-day retention) |
| ECS/RDS Metrics | CPU, Memory, Connections via CloudWatch Metrics |
| Alarms | CloudWatch Alarms for service health thresholds |

---

## 🛠️ Infrastructure — Terraform Resources

| Resource | Details |
|----------|---------|
| **VPC** | 10.0.0.0/16, DNS enabled |
| **Subnets** | 2 public + 2 private across 2 AZs |
| **Internet Gateway** | Public internet access |
| **Security Groups** | ALB → ECS → RDS (least privilege) |
| **ECR** | Private registry, lifecycle policy (keep last 10 images) |
| **RDS PostgreSQL** | db.t3.micro, 20GB, private subnet, 7-day backup |
| **ECS Cluster** | EC2 launch type, t2.micro, Auto Scaling Group |
| **ECS Service** | 2 tasks, rolling update (50% min healthy) |
| **ALB** | Internet-facing, health check on /health |
| **CloudWatch** | Log groups for ECS tasks, 7-day retention |

---

## 🏃 Run Locally

```bash
# Clone the repository
git clone https://github.com/nchoudharygit/notely-devops-app.git
cd notely-devops-app

# Copy environment file
cp .env.example .env
# Edit .env with your database credentials

# Start all services
docker compose up -d

# Verify all containers are running
docker compose ps

# Access the app
open http://localhost:8081
open http://localhost:9001  # MinIO Console
```

---

## ☁️ Deploy to AWS

```bash
# Prerequisites
# - AWS CLI configured (aws configure)
# - Terraform installed (terraform --version)

cd terraform

# Initialize
terraform init

# Preview changes
terraform plan -var='db_password=YOUR_PASSWORD' -var='environment=staging'

# Deploy
terraform apply -var='db_password=YOUR_PASSWORD' -var='environment=staging'

# Get the app URL
terraform output app_url

# Destroy when done (avoid charges!)
terraform destroy -var='db_password=YOUR_PASSWORD' -var='environment=staging'
```

---

## 🔐 Security Practices

- **Keyless CI/CD authentication** — GitHub Actions uses AWS IAM OIDC federation (no long-lived access keys)
- **Least privilege security groups** — ALB → ECS → RDS (no direct internet access to DB)
- **Private subnets for RDS** — database not publicly accessible
- **ECR image scanning** — `scan_on_push = true` for vulnerability detection
- **IAM roles for ECS** — no static credentials on EC2 instances
- **Secrets Manager** — sensitive credentials injected into ECS tasks at runtime, never hardcoded

---

## 🐛 Real Debugging Experiences

| Issue | Root Cause | Fix |
|-------|-----------|-----|
| `docker compose up` path error | docker-compose.yml had `./backend` but code was in `./app/backend` | Updated all paths to `./app/*` |
| ECS tasks not starting | ECS Security Group blocking dynamic bridge-mode ports | Updated security group rules for dynamic port mapping |
| ECR repository conflict | `RepositoryAlreadyExistsException` on terraform apply | Imported existing ECR repo into Terraform state |
| ALB health check failing | `/health` endpoint not returning 200 | Added health check route in PHP app |
| RDS connection refused | ECS security group not in RDS inbound rules | Updated `aws_security_group.rds` ingress |

---

## 📈 Key Metrics (Interview Highlights)

- **2 ECS tasks** running simultaneously for high availability
- **Rolling deployment** — zero downtime updates (50% min healthy)
- **Auto-scaling** — ASG scales EC2 nodes based on demand
- **7-day RDS backups** — automated point-in-time recovery
- **Keyless CI/CD** — OIDC-based authentication, no static AWS credentials in GitHub
