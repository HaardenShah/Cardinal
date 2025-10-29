#!/bin/bash
# Deploy script for portfolio hub
# Usage: ./deploy.sh [user@server:/path/to/project]

set -e

if [ -z "$1" ]; then
    echo "Usage: ./deploy.sh user@server:/path/to/project"
    exit 1
fi

REMOTE=$1

echo "🚀 Deploying to $REMOTE"

# Sync files
rsync -avz --progress \
    --exclude 'data/' \
    --exclude 'uploads/' \
    --exclude 'backups/' \
    --exclude '.git/' \
    --exclude '.env.php' \
    --exclude 'node_modules/' \
    --exclude '.DS_Store' \
    ./ $REMOTE/

echo "✓ Files synced"

# Run post-deploy commands on remote
ssh ${REMOTE%:*} << 'ENDSSH'
cd ${REMOTE#*:}

echo "Setting permissions..."
chmod 755 .
chmod 775 data uploads backups 2>/dev/null || mkdir -p data uploads backups && chmod 775 data uploads backups

echo "Checking database..."
if [ ! -f data/site.db ]; then
    echo "Initializing database..."
    php init-db.php
fi

echo "✓ Deployment complete!"
echo "Next steps:"
echo "1. Update .env.php with production values"
echo "2. Change admin password"
echo "3. Test at: https://yourname.com"
ENDSSH

echo ""
echo "🎉 Deployment complete!"
echo ""
echo "⚠️  Remember to:"
echo "  1. Update .env.php on server"
echo "  2. Change admin password"
echo "  3. Configure web server (Apache/Nginx)"
echo "  4. Test /api/health endpoint"
