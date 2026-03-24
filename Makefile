# JMC Website — Development & Deployment Workflow
# Usage: make <target>

.PHONY: up down logs test deploy deploy-hotfix db-import shell help

# ─── Local Development ─────────────────────────────────────────────────────

up:
	docker-compose up -d
	@echo ""
	@echo "  Site:  http://localhost:8080"
	@echo "  MySQL: localhost:3306  (user: jmc_user / pass: local_dev_password)"
	@echo ""

down:
	docker-compose down

logs:
	docker-compose logs -f php

shell:
	docker-compose exec php bash

# ─── Testing ───────────────────────────────────────────────────────────────

test:
	@echo "=============================="
	@echo "  PHP Syntax Check"
	@echo "=============================="
	@find . -name "*.php" \
	  -not -path "./vendor/*" \
	  -not -path "./docker/*" \
	  -print0 | xargs -0 -n1 php -l
	@echo ""
	@echo "=============================="
	@echo "  Composer Security Audit"
	@echo "=============================="
	@composer audit
	@echo ""
	@echo "=============================="
	@echo "  All checks passed"
	@echo "=============================="

# ─── Deployment ────────────────────────────────────────────────────────────

# Standard deploy: requires tests to pass, then pushes to GitHub
# GitHub Actions handles the rest: re-runs tests → rsync → apache reload
deploy: test
	@echo ""
	@echo "Tests passed. Pushing to GitHub..."
	@git push origin main
	@echo ""
	@echo "  GitHub Actions will now:"
	@echo "  1. Re-run PHP syntax + security checks"
	@echo "  2. rsync files to production server"
	@echo "  3. Run composer install on server"
	@echo "  4. Reload Apache"
	@echo ""
	@echo "  Monitor: https://github.com/$(shell git remote get-url origin | sed 's/.*github.com[:/]//' | sed 's/.git$$//')/actions"
	@echo ""

# Emergency hotfix: bypasses GitHub Actions, deploys directly via rsync
# Only use when GitHub Actions is unavailable or for critical fixes
deploy-hotfix: test
	@echo ""
	@echo "WARNING: Direct deploy bypasses GitHub Actions CI pipeline."
	@read -p "Continue? [y/N] " confirm && [ "$$confirm" = "y" ] || exit 1
	@echo ""
	@echo "Deploying directly to production server..."
	rsync -avzr --delete \
	  --exclude='.git' \
	  --exclude='.github' \
	  --exclude='.env' \
	  --exclude='vendor' \
	  --exclude='.vscode' \
	  --exclude='docker' \
	  --exclude='docker-compose.yml' \
	  --exclude='Makefile' \
	  --exclude='.env.docker' \
	  --exclude='*.md' \
	  --exclude='database/' \
	  --exclude='docs/' \
	  --exclude='n8n/' \
	  --exclude='scripts/' \
	  --exclude='*.sh' \
	  --exclude='.DS_Store' \
	  --exclude='*.backup*' \
	  ./ ubuntu@167.114.97.221:/var/www/html/
	@echo ""
	@echo "Running post-deploy tasks on server..."
	ssh ubuntu@167.114.97.221 "\
	  cd /var/www/html && \
	  composer install --no-dev --optimize-autoloader && \
	  sudo chown -R www-data:www-data /var/www/html && \
	  sudo chmod 775 /var/www/html/images/blog/ && \
	  sudo systemctl reload apache2 && \
	  echo 'Hotfix deployed successfully.'"

# ─── Database ──────────────────────────────────────────────────────────────

db-import:
	@echo "Importing schema into local Docker MySQL..."
	docker-compose exec -T mysql mysql \
	  -u jmc_user -plocal_dev_password jmc_website \
	  < docker/mysql/init.sql
	@echo "Schema imported."

db-shell:
	docker-compose exec mysql mysql -u jmc_user -plocal_dev_password jmc_website

# ─── Help ──────────────────────────────────────────────────────────────────

help:
	@echo ""
	@echo "JMC Website — Available Commands"
	@echo ""
	@echo "  Development:"
	@echo "    make up            Start local PHP + MySQL (http://localhost:8080)"
	@echo "    make down          Stop local environment"
	@echo "    make logs          Tail PHP container logs"
	@echo "    make shell         Open bash shell in PHP container"
	@echo ""
	@echo "  Testing:"
	@echo "    make test          PHP syntax check + composer security audit"
	@echo ""
	@echo "  Deployment:"
	@echo "    make deploy        Test + git push → triggers GitHub Actions CI/CD"
	@echo "    make deploy-hotfix Test + direct rsync to production (emergency only)"
	@echo ""
	@echo "  Database:"
	@echo "    make db-import     Import schema into local Docker MySQL"
	@echo "    make db-shell      Open MySQL shell in Docker"
	@echo ""
