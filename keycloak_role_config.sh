#!/bin/bash
#
# Keycloak Role Configuration Script
# This script helps configure Keycloak roles for Moodle integration
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
KEYCLOAK_URL="http://10.70.5.223:8080"
REALM="master"
ADMIN_USER="admin"
ADMIN_PASS="admin"
CLIENT_ID="moodle-realm"

echo "=========================================="
echo "Keycloak Role Configuration for Moodle"
echo "=========================================="
echo ""

# Function to get admin token
get_admin_token() {
    echo -e "${YELLOW}Getting admin access token...${NC}"
    TOKEN=$(curl -s -X POST \
        "${KEYCLOAK_URL}/realms/${REALM}/protocol/openid-connect/token" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "username=${ADMIN_USER}" \
        -d "password=${ADMIN_PASS}" \
        -d "grant_type=password" \
        -d "client_id=admin-cli" | grep -o '"access_token":"[^"]*' | grep -o '[^"]*$')
    
    if [ -z "$TOKEN" ]; then
        echo -e "${RED}Failed to get admin token. Please check credentials.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Got admin token${NC}"
}

# Function to create realm role
create_realm_role() {
    local role_name=$1
    local role_description=$2
    
    echo -e "${YELLOW}Creating realm role: ${role_name}${NC}"
    
    # Check if role exists
    EXISTING_ROLE=$(curl -s -X GET \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/roles/${role_name}" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" | grep -o '"name":"[^"]*' | grep -o '[^"]*$' || true)
    
    if [ "$EXISTING_ROLE" = "$role_name" ]; then
        echo -e "${GREEN}✓ Role '${role_name}' already exists${NC}"
        return
    fi
    
    # Create role
    curl -s -X POST \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/roles" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"${role_name}\",\"description\":\"${role_description}\",\"composite\":false,\"clientRole\":false}"
    
    echo -e "${GREEN}✓ Created role '${role_name}'${NC}"
}

# Function to create client scope
create_client_scope() {
    local scope_name=$1
    
    echo -e "${YELLOW}Creating client scope: ${scope_name}${NC}"
    
    # Check if scope exists
    EXISTING_SCOPE=$(curl -s -X GET \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/client-scopes" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" | grep -o "\"name\":\"${scope_name}\"" || true)
    
    if [ ! -z "$EXISTING_SCOPE" ]; then
        echo -e "${GREEN}✓ Client scope '${scope_name}' already exists${NC}"
        return
    fi
    
    # Create client scope
    curl -s -X POST \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/client-scopes" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"${scope_name}\",\"protocol\":\"openid-connect\",\"attributes\":{\"display.on.consent.screen\":\"false\"}}"
    
    echo -e "${GREEN}✓ Created client scope '${scope_name}'${NC}"
}

# Function to add realm role mapper to client scope
add_realm_role_mapper() {
    local scope_name=$1
    
    echo -e "${YELLOW}Adding realm role mapper to scope: ${scope_name}${NC}"
    
    # Get client scope ID
    SCOPE_ID=$(curl -s -X GET \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/client-scopes" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" | grep -o '"id":"[^"]*","name":"'${scope_name}'"' | grep -o '"id":"[^"]*"' | head -1 | grep -o '[^"]*$')
    
    if [ -z "$SCOPE_ID" ]; then
        echo -e "${RED}Failed to get client scope ID${NC}"
        return
    fi
    
    # Add realm role mapper
    curl -s -X POST \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/client-scopes/${SCOPE_ID}/protocol-mappers/models" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -d '{
            "name":"realm roles",
            "protocol":"openid-connect",
            "protocolMapper":"oidc-usermodel-realm-role-mapper",
            "consentRequired":false,
            "config":{
                "multivalued":"true",
                "userinfo.token.claim":"true",
                "id.token.claim":"true",
                "access.token.claim":"true",
                "claim.name":"realm_access.roles",
                "jsonType.label":"String"
            }
        }'
    
    echo -e "${GREEN}✓ Added realm role mapper${NC}"
}

# Function to assign client scope to client
assign_scope_to_client() {
    local scope_name=$1
    
    echo -e "${YELLOW}Assigning scope '${scope_name}' to client '${CLIENT_ID}'${NC}"
    
    # Get client ID
    CLIENT_UUID=$(curl -s -X GET \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/clients" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" | grep -o '"id":"[^"]*","clientId":"'${CLIENT_ID}'"' | grep -o '"id":"[^"]*"' | head -1 | grep -o '[^"]*$')
    
    if [ -z "$CLIENT_UUID" ]; then
        echo -e "${RED}Client '${CLIENT_ID}' not found${NC}"
        return
    fi
    
    # Get client scope ID
    SCOPE_ID=$(curl -s -X GET \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/client-scopes" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" | grep -o '"id":"[^"]*","name":"'${scope_name}'"' | grep -o '"id":"[^"]*"' | head -1 | grep -o '[^"]*$')
    
    if [ -z "$SCOPE_ID" ]; then
        echo -e "${RED}Client scope '${scope_name}' not found${NC}"
        return
    fi
    
    # Assign scope to client as default
    curl -s -X PUT \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/clients/${CLIENT_UUID}/default-client-scopes/${SCOPE_ID}" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json"
    
    echo -e "${GREEN}✓ Assigned scope to client${NC}"
}

# Function to assign role to user
assign_role_to_user() {
    local username=$1
    local role_name=$2
    
    echo -e "${YELLOW}Assigning role '${role_name}' to user '${username}'${NC}"
    
    # Get user ID
    USER_ID=$(curl -s -X GET \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/users?username=${username}" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" | grep -o '"id":"[^"]*"' | head -1 | grep -o '[^"]*$')
    
    if [ -z "$USER_ID" ]; then
        echo -e "${RED}User '${username}' not found${NC}"
        return
    fi
    
    # Get role representation
    ROLE_JSON=$(curl -s -X GET \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/roles/${role_name}" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json")
    
    # Assign role to user
    curl -s -X POST \
        "${KEYCLOAK_URL}/admin/realms/${REALM}/users/${USER_ID}/role-mappings/realm" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -d "[${ROLE_JSON}]"
    
    echo -e "${GREEN}✓ Assigned role to user${NC}"
}

# Main execution
echo ""
echo "Step 1: Authenticating with Keycloak..."
get_admin_token

echo ""
echo "Step 2: Creating Realm Roles..."
create_realm_role "moodle-admin" "Moodle Site Administrator"
create_realm_role "moodle-teacher" "Moodle Teacher"
create_realm_role "moodle-student" "Moodle Student"

echo ""
echo "Step 3: Creating Client Scope..."
create_client_scope "moodle-roles"

echo ""
echo "Step 4: Adding Role Mapper..."
add_realm_role_mapper "moodle-roles"

echo ""
echo "Step 5: Assigning Scope to Client..."
assign_scope_to_client "moodle-roles"

echo ""
echo "=========================================="
echo -e "${GREEN}Keycloak Configuration Complete!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Assign roles to users in Keycloak"
echo "2. Configure Moodle plugin settings"
echo "3. Test login with a user that has roles"
echo ""
echo "To assign roles to users, use:"
echo "  ./keycloak_role_config.sh assign <username> <role>"
echo ""
