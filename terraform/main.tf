resourccccce "aws_security_group" "sg" {
  name = "test"

  ingress { from_port = 80 to_port = 80 protocol = "tcp" cidr_blocks = ["0.0.0.0/0"] }
}