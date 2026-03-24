# GitHub Workflows

This directory contains GitHub Actions workflows for automated CI/CD.

## Workflows

### deploy.yml
Automated deployment to production server.

**Triggers:**
- Push to `main` branch
- Manual trigger via GitHub Actions UI

**Jobs:**
1. **Test:** PHP syntax validation, Composer checks
2. **Deploy:** Rsync to production server, install dependencies, set permissions

**Required Secrets:**
- `DEPLOY_HOST` - Production server IP (167.114.97.221)
- `DEPLOY_USER` - SSH username (ubuntu)
- `DEPLOY_SSH_KEY` - Private SSH key for deployment

## Setup Instructions

See [DEPLOYMENT.md](../DEPLOYMENT.md) for complete setup and configuration guide.

## Monitoring

View workflow runs: https://github.com/YOUR_USERNAME/jmc-website/actions
