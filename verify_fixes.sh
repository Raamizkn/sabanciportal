#!/bin/bash

echo "=========================================="
echo "API Verification Script"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

COOKIE_FILE="/tmp/verify_test_cookies.txt"

echo -e "${YELLOW}1. Testing Login...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST 'http://localhost:8001/index.php?entity=auth&action=login' \
  -H 'Content-Type: application/json' \
  -c "$COOKIE_FILE" \
  -d '{"email":"admin@example.com","password":"password123","role":"admin"}')

if echo "$LOGIN_RESPONSE" | grep -q "success"; then
  echo -e "${GREEN}✓ Login successful${NC}"
else
  echo -e "${RED}✗ Login failed${NC}"
  echo "$LOGIN_RESPONSE"
  exit 1
fi

echo ""
echo -e "${YELLOW}2. Testing Get All Applications...${NC}"
APPS_RESPONSE=$(curl -s -X GET 'http://localhost:8001/index.php?entity=applications' \
  -H 'Content-Type: application/json' \
  -b "$COOKIE_FILE")

# Check if it's valid JSON array
if echo "$APPS_RESPONSE" | python3 -c "import sys, json; json.load(sys.stdin)" 2>/dev/null; then
  APP_COUNT=$(echo "$APPS_RESPONSE" | python3 -c "import sys, json; print(len(json.load(sys.stdin)))")
  echo -e "${GREEN}✓ Got $APP_COUNT applications${NC}"
  
  # Check required fields
  FIRST_APP=$(echo "$APPS_RESPONSE" | python3 -c "import sys, json; apps=json.load(sys.stdin); print(json.dumps(apps[0] if apps else {}))")
  
  echo ""
  echo -e "${YELLOW}3. Checking Required Fields in First Application...${NC}"
  
  FIELDS=("company_id" "student_id" "internship_id" "student_name" "company_name" "internship_position")
  ALL_PRESENT=true
  
  for field in "${FIELDS[@]}"; do
    if echo "$FIRST_APP" | python3 -c "import sys, json; app=json.load(sys.stdin); sys.exit(0 if '$field' in app and app['$field'] else 1)" 2>/dev/null; then
      VALUE=$(echo "$FIRST_APP" | python3 -c "import sys, json; app=json.load(sys.stdin); print(app.get('$field', 'N/A'))")
      echo -e "${GREEN}  ✓ $field: $VALUE${NC}"
    else
      echo -e "${RED}  ✗ $field: MISSING${NC}"
      ALL_PRESENT=false
    fi
  done
  
  if [ "$ALL_PRESENT" = true ]; then
    echo ""
    echo -e "${GREEN}✓ All required fields present!${NC}"
  else
    echo ""
    echo -e "${RED}✗ Some required fields are missing${NC}"
    exit 1
  fi
else
  echo -e "${RED}✗ Invalid JSON response${NC}"
  echo "$APPS_RESPONSE" | head -5
  exit 1
fi

echo ""
echo -e "${YELLOW}4. Testing Company Profile Access...${NC}"
COMPANY_RESPONSE=$(curl -s -X GET 'http://localhost:8001/index.php?entity=admin&resource=companies&id=1' \
  -H 'Content-Type: application/json' \
  -b "$COOKIE_FILE")

if echo "$COMPANY_RESPONSE" | python3 -c "import sys, json; data=json.load(sys.stdin); sys.exit(0 if data.get('id') else 1)" 2>/dev/null; then
  COMPANY_NAME=$(echo "$COMPANY_RESPONSE" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('name', 'N/A'))")
  echo -e "${GREEN}✓ Company profile accessible: $COMPANY_NAME${NC}"
else
  echo -e "${RED}✗ Company profile access failed${NC}"
  echo "$COMPANY_RESPONSE" | head -3
fi

echo ""
echo "=========================================="
echo -e "${GREEN}All tests passed!${NC}"
echo "=========================================="
