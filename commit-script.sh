#!/bin/bash

# Navigate to repository directory
cd /Users/video/Downloads/financial-email-client

# Add all modified files
git add .

# Create commit
git commit -m "Add export feature, enhance financial analysis with investment tracking, improve email connector with ProtonMail support"

# Push changes
git push origin main

# Cleanup this script
rm -- "$0"
