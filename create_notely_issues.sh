#!/bin/bash

# ================================================
# Notely DevOps Project — GitHub Issues Creator
# Run: bash create_notely_issues.sh
# Requires: gh CLI (github.com/cli/gh)
# ================================================

REPO="nchoudharygit/notely-devops-app"

echo "🚀 Creating labels..."

gh label create "phase-0"       --color "0075ca" --description "Prerequisites"           --repo $REPO --force
gh label create "phase-1"       --color "0075ca" --description "Project Structure"        --repo $REPO --force
gh label create "phase-2"       --color "0075ca" --description "Remote State"             --repo $REPO --force
gh label create "phase-3"       --color "0075ca" --description "ECR"                      --repo $REPO --force
gh label create "phase-4"       --color "0075ca" --description "Networking"               --repo $REPO --force
gh label create "phase-5"       --color "0075ca" --description "Secrets Manager"          --repo $REPO --force
gh label create "phase-6"       --color "0075ca" --description "RDS"                      --repo $REPO --force
gh label create "phase-7"       --color "0075ca" --description "ECS + ALB"                --repo $REPO --force
gh label create "phase-8"       --color "0075ca" --description "CI/CD"                    --repo $REPO --force
gh label create "phase-9"       --color "0075ca" --description "Monitoring"               --repo $REPO --force
gh label create "phase-10"      --color "0075ca" --description "Cleanup"                  --repo $REPO --force
gh label create "terraform"     --color "6f42c1" --description "Terraform related"        --repo $REPO --force
gh label create "aws"           --color "e4a000" --description "AWS related"              --repo $REPO --force
gh label create "networking"    --color "d93f0b" --description "VPC/Networking"           --repo $REPO --force
gh label create "security"      --color "b60205" --description "Security related"         --repo $REPO --force
gh label create "database"      --color "e11d48" --description "Database related"         --repo $REPO --force
gh label create "ci-cd"         --color "0e8a16" --description "CI/CD pipelines"          --repo $REPO --force
gh label create "monitoring"    --color "5319e7" --description "Monitoring stack"         --repo $REPO --force
gh label create "critical"      --color "c00000" --description "Critical — charges apply" --repo $REPO --force
gh label create "cleanup"       --color "333333" --description "Cleanup tasks"            --repo $REPO --force

echo "✅ Labels created!"
echo ""
echo "🚀 Creating issues..."

# ── Issue 1 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 0] Prerequisites & Local Setup" \
  --label "phase-0" \
  --body "## Goal
Verify all tools and local app before AWS deployment.

## Tasks
- [ ] \`aws sts get-caller-identity\` — verify AWS credentials configured
- [ ] \`terraform --version\` — v1.5+ confirm
- [ ] \`docker --version\` — v24+ confirm
- [ ] \`docker compose up -d\` — local app working verify karo
- [ ] \`docker compose ps\` — all containers Running
- [ ] \`docker compose down\` — shutdown before AWS deploy"

echo "✅ Issue 1 created"

# ── Issue 2 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 1] Project Structure Verification" \
  --label "phase-1" \
  --body "## Goal
Verify and create all required folders for the project.

## Tasks
- [ ] \`terraform/modules/\` folder exists
- [ ] \`.github/workflows/\` folder exists
- [ ] \`monitoring/prometheus/\` folder exists
- [ ] \`monitoring/grafana/dashboards/\` folder exists
- [ ] \`monitoring/alertmanager/\` folder exists
- [ ] \`tree -L 2\` run karo — structure verify karo"

echo "✅ Issue 2 created"

# ── Issue 3 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 2] Terraform Remote State — S3 + DynamoDB" \
  --label "phase-2,terraform" \
  --body "## Goal
Setup remote state before any \`terraform apply\`. This is production must-have.

## Tasks
- [ ] AWS Account ID note karo — \`aws sts get-caller-identity --query Account --output text\`
- [ ] S3 bucket create karo — \`notely-terraform-state-<ACCOUNT_ID>\`
- [ ] S3 bucket pe versioning enable karo
- [ ] S3 bucket pe public access block karo
- [ ] DynamoDB table create karo — \`notely-terraform-locks\` with \`LockID\` partition key, PAY_PER_REQUEST
- [ ] \`terraform/backend.tf\` create karo — bucket, key, region, dynamodb_table, encrypt=true
- [ ] \`terraform/variables.tf\` verify/update — aws_region, environment, db_password, app_image_tag, grafana_password
- [ ] \`terraform/provider.tf\` verify — required_providers aws ~> 5.0
- [ ] \`cd terraform && terraform init\` — 'Successfully initialized' confirm
- [ ] Verify — S3 Console mein bucket dikhna chahiye

## Note
State locking ensures two people cannot run \`terraform apply\` simultaneously."

echo "✅ Issue 3 created"

# ── Issue 4 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 3] ECR — Docker Image Registry" \
  --label "phase-3,docker,aws" \
  --body "## Goal
Create ECR repository and push first Docker image to AWS.

## Tasks
- [ ] \`terraform/ecr.tf\` verify/update — aws_ecr_repository, scan_on_push=true, lifecycle_policy (keep last 10 images), output ecr_url
- [ ] \`terraform plan\` — preview changes
- [ ] \`terraform apply\` — ECR repository create karo
- [ ] Verify — AWS Console → ECR → \`notely-app\` repository visible
- [ ] ECR URL note karo — \`terraform output ecr_url\`
- [ ] ECR login — \`aws ecr get-login-password | docker login\`
- [ ] \`docker build -t notely-app:latest .\`
- [ ] Image tag karo — \`latest\` aur \`v1.0\` dono
- [ ] \`docker push\` both tags to ECR
- [ ] Verify — AWS Console → ECR → Images → \`latest\` aur \`v1.0\` visible"

echo "✅ Issue 4 created"

# ── Issue 5 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 4] Networking — VPC + Subnets + VPC Endpoints" \
  --label "phase-4,networking,terraform" \
  --body "## Goal
Create full networking layer with PrivateLinks. No NAT Gateway needed!

## Tasks

### networking.tf
- [ ] Verify/update \`terraform/networking.tf\`:
  - [ ] \`aws_vpc\` — cidr \`10.0.0.0/16\`, dns_hostnames=true, dns_support=true
  - [ ] \`aws_subnet\` public — count 2, map_public_ip_on_launch=true, 2 AZs
  - [ ] \`aws_subnet\` private — count 2, 2 AZs
  - [ ] \`aws_internet_gateway\`
  - [ ] \`aws_route_table\` public — route 0.0.0.0/0 to IGW
  - [ ] \`aws_route_table_association\` public subnets
  - [ ] \`data aws_availability_zones\`

### vpc_endpoints.tf (NEW FILE)
- [ ] Create \`terraform/vpc_endpoints.tf\`:
  - [ ] Security group for endpoints — port 443 from VPC CIDR
  - [ ] S3 Gateway endpoint — FREE
  - [ ] ECR API Interface endpoint — private_dns_enabled=true
  - [ ] ECR DKR Interface endpoint — private_dns_enabled=true
  - [ ] Secrets Manager Interface endpoint — private_dns_enabled=true
  - [ ] CloudWatch Logs Interface endpoint — private_dns_enabled=true

### security_groups.tf (NEW FILE)
- [ ] Create \`terraform/security_groups.tf\`:
  - [ ] ALB SG — inbound port 80 from 0.0.0.0/0
  - [ ] ECS SG — inbound port 80 from ALB SG only
  - [ ] RDS SG — inbound port 5432 from ECS SG only

### Apply + Verify
- [ ] \`terraform apply\`
- [ ] Verify — AWS Console → VPC → \`notely-vpc-staging\` visible
- [ ] Verify — AWS Console → VPC → Endpoints → 5 endpoints created

## Interview Note
VPC Endpoints = PrivateLinks. ECS tasks access ECR and Secrets Manager privately without NAT Gateway."

echo "✅ Issue 5 created"

# ── Issue 6 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 5] Secrets Manager — Secure Credentials" \
  --label "phase-5,security,aws" \
  --body "## Goal
Store all sensitive values in AWS Secrets Manager. No hardcoded passwords!

## Tasks
- [ ] DB password secret create karo:
  \`\`\`
  aws secretsmanager create-secret --name notely/staging/db-password --secret-string 'Test1234!' --region ap-south-1
  \`\`\`
- [ ] Grafana password secret create karo:
  \`\`\`
  aws secretsmanager create-secret --name notely/staging/grafana-password --secret-string 'Admin@123' --region ap-south-1
  \`\`\`
- [ ] Create \`terraform/secrets.tf\`:
  - [ ] \`data aws_secretsmanager_secret\` for db-password
  - [ ] \`data aws_secretsmanager_secret_version\` for db-password
  - [ ] \`data aws_secretsmanager_secret\` for grafana-password
  - [ ] \`data aws_secretsmanager_secret_version\` for grafana-password
  - [ ] \`locals\` block — db_password + grafana_password
- [ ] \`terraform plan\` — verify secrets data sources resolve
- [ ] Verify — AWS Console → Secrets Manager → both secrets visible

## Note
Use \`local.db_password\` in RDS and ECS — not \`var.db_password\`"

echo "✅ Issue 6 created"

# ── Issue 7 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 6] RDS — Managed PostgreSQL" \
  --label "phase-6,database,aws,critical" \
  --body "## Goal
Create managed PostgreSQL database on AWS RDS.

## ⚠️ WARNING
RDS charges apply beyond free tier 750 hrs/month. **Create, test, and destroy same day!**

## Tasks
- [ ] Create \`terraform/rds.tf\`:
  - [ ] \`aws_db_subnet_group\` — use private subnets
  - [ ] \`aws_db_instance\`:
    - engine = postgres, version = 15
    - instance_class = db.t3.micro (FREE TIER)
    - allocated_storage = 20
    - password = \`local.db_password\` (from Secrets Manager)
    - publicly_accessible = false
    - skip_final_snapshot = true
    - backup_retention_period = 7
  - [ ] output \`db_endpoint\`
- [ ] \`terraform apply\` — wait 5-10 minutes
- [ ] \`terraform output db_endpoint\` — note the endpoint URL
- [ ] Verify — AWS Console → RDS → \`notely-db-staging\` status = Available"

echo "✅ Issue 7 created"

# ── Issue 8 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 7] ECS + ALB — Application Deployment" \
  --label "phase-7,aws,critical" \
  --body "## Goal
Deploy Notely application on ECS with Application Load Balancer.

## ⚠️ WARNING
ALB is NOT free tier (~\$0.008/hr). **Destroy same day after testing!**

## Tasks

### iam.tf (NEW FILE)
- [ ] Create \`terraform/iam.tf\`:
  - [ ] \`aws_iam_role\` ecs_execution — assume role for ecs-tasks.amazonaws.com
  - [ ] \`aws_iam_role_policy_attachment\` — AmazonECSTaskExecutionRolePolicy
  - [ ] \`aws_iam_role_policy\` ecs_secrets — secretsmanager:GetSecretValue for notely/*
  - [ ] \`aws_iam_role\` ecs_node — for ec2.amazonaws.com
  - [ ] \`aws_iam_role_policy_attachment\` ecs_node — AmazonEC2ContainerServiceforEC2Role
  - [ ] \`aws_iam_instance_profile\` ecs_node
  - [ ] \`aws_cloudwatch_log_group\` — /ecs/notely-staging, retention 7 days

### ecs.tf (NEW FILE)
- [ ] Create \`terraform/ecs.tf\`:
  - [ ] \`aws_ecs_cluster\`
  - [ ] \`aws_launch_template\` — t2.micro, ECS optimized AMI, user_data with cluster name
  - [ ] \`aws_autoscaling_group\` — desired=1, max=2, min=1
  - [ ] \`aws_ecs_task_definition\` — image from ECR, env vars, local.db_password, CloudWatch logs config
  - [ ] \`aws_ecs_service\` — desired_count=2, load_balancer config, min_healthy=50%, max=200%
  - [ ] \`data aws_ami\` — latest ECS optimized AMI

### alb.tf (NEW FILE)
- [ ] Create \`terraform/alb.tf\`:
  - [ ] \`aws_lb\` — application type, public subnets
  - [ ] \`aws_lb_target_group\` — port 80, health_check path=/health
  - [ ] \`aws_lb_listener\` — port 80, forward to target group
  - [ ] output \`app_url\`

### App Update
- [ ] PHP app mein \`/health\` endpoint add karo — return \`{\"status\": \"ok\"}\`

### Apply + Verify
- [ ] \`terraform apply\`
- [ ] \`terraform output app_url\` — URL note karo
- [ ] \`curl <app_url>/health\` — \`{\"status\":\"ok\"}\` aana chahiye
- [ ] Verify — ECS Console → Service ACTIVE, 2 tasks RUNNING
- [ ] Verify — ALB Console → Target group → Healthy targets"

echo "✅ Issue 8 created"

# ── Issue 9 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 8] GitHub Actions — CI/CD Pipeline" \
  --label "phase-8,ci-cd" \
  --body "## Goal
Automate build and deploy on every push to main.

## Tasks

### GitHub Secrets Setup
- [ ] GitHub repo → Settings → Secrets → Actions → Add:
  - [ ] \`AWS_ACCESS_KEY_ID\`
  - [ ] \`AWS_SECRET_ACCESS_KEY\`
  - [ ] \`ECR_REGISTRY\` — \`<account>.dkr.ecr.ap-south-1.amazonaws.com\`
  - [ ] \`TF_STATE_BUCKET\` — S3 bucket name

### CI Pipeline
- [ ] Create/verify \`.github/workflows/ci.yml\`:
  - [ ] trigger: pull_request on main
  - [ ] steps: checkout → AWS credentials → ECR login → docker build → push with github.sha tag

### CD Staging Pipeline
- [ ] Create/verify \`.github/workflows/deploy-staging.yml\`:
  - [ ] trigger: push on main
  - [ ] steps: checkout → AWS creds → ECR login → build+push → terraform init → terraform apply → health check

### CD Production Pipeline
- [ ] Create \`.github/workflows/deploy-prod.yml\`:
  - [ ] trigger: workflow_dispatch (manual only)
  - [ ] environment: production (requires approval)
  - [ ] input: image_tag

### GitHub Environment Setup
- [ ] GitHub → Settings → Environments → Create \`production\`
- [ ] Add yourself as Required Reviewer

### Test Full Flow
- [ ] \`git checkout -b feature/test-pipeline\`
- [ ] Small change karo, push karo
- [ ] PR create karo → CI pipeline green ✅
- [ ] PR merge karo → Staging deploy triggered ✅
- [ ] \`curl <staging_url>/health\` → ok ✅
- [ ] GitHub → Actions → Deploy to Production → Run → Approve → Verify ✅"

echo "✅ Issue 9 created"

# ── Issue 10 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 9] Monitoring — Prometheus + Grafana + Alertmanager" \
  --label "phase-9,monitoring" \
  --body "## Goal
Setup production-grade monitoring with alerts to Slack.

## Tasks

### Config Files
- [ ] Create \`monitoring/prometheus/prometheus.yml\` — global scrape_interval 15s, alertmanager target, scrape configs for notely-app + node-exporter
- [ ] Create \`monitoring/prometheus/alerts.yml\` — AppDown (30s), HighCPU (>80% for 2m), HighMemory (>85% for 2m) rules
- [ ] Create \`monitoring/alertmanager/alertmanager.yml\` — Slack webhook URL, #devops-alerts channel

### Terraform
- [ ] Create \`terraform/monitoring.tf\`:
  - [ ] Prometheus ECS task definition + service — prom/prometheus:latest, port 9090
  - [ ] Grafana ECS task definition + service — grafana/grafana:latest, port 3000, local.grafana_password

### Deploy + Setup
- [ ] \`terraform apply\`
- [ ] EC2 public IP find karo: \`aws ec2 describe-instances --filters 'Name=tag:Name,Values=notely-ecs-*'\`
- [ ] Temporarily allow port 3000 from your IP
- [ ] Browser → \`http://<EC2_IP>:3000\` — Grafana login (admin/Admin@123)
- [ ] Grafana → Connections → Data Sources → Add Prometheus → URL: http://localhost:9090 → Save & Test
- [ ] Import dashboard ID \`1860\` — Node Exporter Full
- [ ] Verify — metrics visible on dashboard ✅
- [ ] Remove port 3000 access after setup"

echo "✅ Issue 10 created"

# ── Issue 11 ──────────────────────────────────────
gh issue create \
  --repo $REPO \
  --title "[Phase 10] Cleanup — Terraform Destroy" \
  --label "phase-10,cleanup,critical" \
  --body "## Goal
Destroy all AWS resources after testing to avoid charges.

## ⚠️ CRITICAL
Always run terraform destroy after testing! RDS + EC2 + ALB running 24/7 will exceed free tier.

## Tasks
- [ ] \`terraform plan -destroy -var='environment=staging'\` — preview what will be deleted
- [ ] \`terraform destroy -var='environment=staging' -auto-approve\`
- [ ] Verify — \`aws ecs list-clusters\` → empty
- [ ] Verify — \`aws rds describe-db-instances\` → empty
- [ ] Verify — \`aws elbv2 describe-load-balancers\` → empty
- [ ] Optional — delete secrets:
  \`\`\`
  aws secretsmanager delete-secret --secret-id notely/staging/db-password --force-delete-without-recovery
  aws secretsmanager delete-secret --secret-id notely/staging/grafana-password --force-delete-without-recovery
  \`\`\`
- [ ] AWS Console manually verify — no leftover resources

## Cost Check
After destroy, check AWS Billing dashboard to confirm \$0 charges."

echo "✅ Issue 11 created"

echo ""
echo "🎉 All done! 11 issues + 20 labels created on $REPO"
echo "👉 Check: https://github.com/$REPO/issues"


##### Remove failed workflow runs (optional cleanup)
# echo "🧹 Cleaning up failed workflow runs..."
## gh run list --status failure --json databaseId --jq '.[].databaseId' | \
# while read id; do
 # echo "Deleting run $id"
 # gh run delete "$id"
#done
# echo "✅ Failed workflow runs cleaned up!"

