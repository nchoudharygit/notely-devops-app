#!/bin/bash
set -e

ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
BUCKET_NAME="notely-terraform-state-${ACCOUNT_ID}"
REGION=${AWS_DEFAULT_REGION:-ap-south-1}

echo "Account ID : $ACCOUNT_ID"
echo "Bucket     : $BUCKET_NAME"
echo "Region     : $REGION"

aws s3api create-bucket \
  --bucket $BUCKET_NAME \
  --region $REGION \
  --create-bucket-configuration LocationConstraint=$REGION

aws s3api put-bucket-versioning \
  --bucket $BUCKET_NAME \
  --versioning-configuration Status=Enabled

aws s3api put-public-access-block \
  --bucket $BUCKET_NAME \
  --public-access-block-configuration \
    BlockPublicAcls=true,IgnorePublicAcls=true,\
    BlockPublicPolicy=true,RestrictPublicBuckets=true

aws dynamodb create-table \
  --table-name notely-terraform-locks \
  --billing-mode PAY_PER_REQUEST \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --region $REGION

echo ""
echo "Done! Add this to backend.tf:"
echo "  bucket = \"$BUCKET_NAME\""
