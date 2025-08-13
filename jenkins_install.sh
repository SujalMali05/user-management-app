#!/bin/bash

set -e  # Exit on any error

# Colors for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Jenkins Installation Script for AWS EC2 (Ubuntu)${NC}"
echo "=================================================="

# Function to print status
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root. Run as ubuntu user."
   exit 1
fi

# Update system
echo -e "${BLUE}📦 Updating system packages...${NC}"
sudo apt update -y
sudo apt upgrade -y
print_status "System updated successfully"

# Install prerequisites (curl, wget, Java etc.)
echo -e "${BLUE}📋 Installing prerequisites...${NC}"
sudo apt install -y \
    curl \
    wget \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    software-properties-common \
    fontconfig \
    openjdk-17-jdk
print_status "Prerequisites installed"

# Verify Java installation
echo -e "${BLUE}☕ Verifying Java installation...${NC}"
java -version || { print_error "Java installation failed"; exit 1; }
JAVA_VERSION=$(java -version 2>&1 | awk -F '"' '/version/ {print $2}' | cut -d'.' -f1)
if [[ $JAVA_VERSION -lt 17 ]]; then
    print_error "Java 17+ is required. Found Java $JAVA_VERSION"
    exit 1
fi
print_status "Java 17+ is available"

#############################################
# New Simplified Jenkins Installation Steps #
#############################################

echo -e "${BLUE}🛠️ Installing Jenkins (Clean Method)...${NC}"

# Add Jenkins GPG key and repo
wget -q -O - https://pkg.jenkins.io/debian/jenkins.io.key | sudo tee /usr/share/keyrings/jenkins-keyring.asc > /dev/null
echo deb [signed-by=/usr/share/keyrings/jenkins-keyring.asc] https://pkg.jenkins.io/debian binary/ | sudo tee /etc/apt/sources.list.d/jenkins.list

# Update package list
sudo apt update -y

# Install Jenkins
sudo apt install -y jenkins
print_status "Jenkins installed"

# Start and enable Jenkins
sudo systemctl start jenkins
sudo systemctl enable jenkins
print_status "Jenkins service started and enabled"

# Install essential utilities to ensure nohup etc. exist
sudo apt install -y coreutils dash bash
print_status "Core utilities installed"

# Verify /bin/sh link
ls -l /bin/sh
# Verify nohup
which nohup
nohup --version

# Restart Jenkins to pick up any environment changes
sudo systemctl restart jenkins

# Show Jenkins service status
sudo systemctl status jenkins --no-pager

# Show Jenkins initial admin password
echo "Initial Jenkins admin password:"
sudo cat /var/lib/jenkins/secrets/initialAdminPassword
echo -e "${GREEN}Jenkins installation completed. Access it at http://<your-server-ip>:8080${NC}"

################################
# The rest of your script below
################################

# Install Docker
echo -e "${BLUE}🐳 Installing Docker...${NC}"
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
| sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update -y
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
print_status "Docker installed"

# Add Jenkins and current user to docker group
sudo usermod -aG docker jenkins
sudo usermod -aG docker $USER
print_status "Users added to docker group"

# Install Docker Compose (standalone)
echo -e "${BLUE}🐙 Installing Docker Compose...${NC}"
DOCKER_COMPOSE_VERSION="v2.20.2"
sudo curl -L "https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
sudo ln -sf /usr/local/bin/docker-compose /usr/bin/docker-compose
print_status "Docker Compose installed"

# Install Git
echo -e "${BLUE}📝 Installing Git...${NC}"
sudo apt install -y git
print_status "Git installed"

# Restart Jenkins after adding to docker group
echo -e "${BLUE}🔄 Restarting Jenkins to apply group changes...${NC}"
sudo systemctl restart jenkins
sleep 10

# Final verifications
if sudo systemctl is-active --quiet jenkins; then
    print_status "Jenkins service: RUNNING"
else
    print_error "Jenkins service: FAILED"
fi

if sudo systemctl is-active --quiet docker; then
    print_status "Docker service: RUNNING"
else
    print_warning "Docker service: NOT RUNNING"
fi

###########################################
# Detect and Display Jenkins Access Info  #
###########################################

# Public IP detection function
get_public_ip() {
    local ip=""
    echo -e "${BLUE}🌐 Detecting public IP address...${NC}"

    # AWS metadata service
    ip=$(curl -s --connect-timeout 5 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null)
    if [[ -n "$ip" && "$ip" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        print_status "Public IP detected from AWS metadata: $ip"
        echo "$ip"
        return 0
    fi

    # External IP services fallback
    local services=("ifconfig.me" "icanhazip.com" "checkip.amazonaws.com")
    for service in "${services[@]}"; do
        ip=$(curl -s --connect-timeout 5 "$service" 2>/dev/null | tr -d '\n\r ')
        if [[ -n "$ip" && "$ip" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            print_status "Public IP detected from $service: $ip"
            echo "$ip"
            return 0
        fi
    done

    # Local IP fallback
    ip=$(hostname -I | awk '{print $1}')
    if [[ -n "$ip" && "$ip" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        print_warning "Using local IP address: $ip"
        echo "$ip"
        return 0
    fi

    print_warning "Could not detect public IP automatically."
    echo ""
    return 1
}

PUBLIC_IP=$(get_public_ip)

echo ""
echo -e "${GREEN}🎉 INSTALLATION COMPLETED SUCCESSFULLY! 🎉${NC}"
echo "=================================================="
echo -e "${BLUE}Jenkins Information:${NC}"

# Enhanced URL display
if [[ "$PUBLIC_IP" == "" ]]; then
    echo -e "📍 URL: ${GREEN}http://[YOUR-PUBLIC-IP]:8080${NC}"
    echo -e "${YELLOW}   ⚠  Replace [YOUR-PUBLIC-IP] with your EC2 public IP${NC}"
    echo -e "${YELLOW}   💡 Get your IP: ${GREEN}curl -s ifconfig.me${NC}"
else
    echo -e "📍 URL: ${GREEN}http://${PUBLIC_IP}:8080${NC}"
    echo -e "✅ Direct access link ready!"
fi


# Try to read the Jenkins initial admin password
PASSWORD=$(sudo cat /var/lib/jenkins/secrets/initialAdminPassword 2>/dev/null || true)

if [[ -n "$PASSWORD" ]]; then
    # ✅ Print it directly if available
    echo -e "${GREEN}🔑 Jenkins Initial Admin Password:${NC} ${YELLOW}$PASSWORD${NC}"
else
    # ⚠ File not found or Jenkins not running — show instructions
    echo -e "${RED}⚠ Could not read the Jenkins initial admin password automatically.${NC}"
    echo -e "🔑 Initial Password Location: ${YELLOW}/var/lib/jenkins/secrets/initialAdminPassword${NC}"
    echo ""
    echo -e "${YELLOW}📝 Note: To access the Jenkins initial password:${NC}"
    echo -e "   • ${GREEN}sudo cat /var/lib/jenkins/secrets/initialAdminPassword${NC}"
    echo -e "   • ${GREEN}sudo -u jenkins cat /var/lib/jenkins/secrets/initialAdminPassword${NC}"
    echo -e "   • Or: ${GREEN}sudo -i${NC} then navigate to the file"
    echo ""
fi


echo ""
echo -e "${BLUE}System Information:${NC}"
echo -e "☕ Java Version: $(java -version 2>&1 | head -n1)"
echo -e "🐳 Docker Version: $(docker --version)"
echo -e "🐙 Docker Compose: $(docker-compose --version)"
echo -e "📝 Git Version: $(git --version)"