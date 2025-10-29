# Test Plan - Portfolio Hub

## Pre-Deployment Tests

### 1. Installation & Setup
- [ ] Files upload successfully
- [ ] Database initializes without errors
- [ ] Admin user created
- [ ] Permissions set correctly (755 for dirs, 644 for files)
- [ ] `.env.php` configured

### 2. Authentication & Security

**Login Tests**
- [ ] Admin can login with correct credentials
- [ ] Login fails with incorrect password
- [ ] Login fails with non-existent email
- [ ] Rate limiting triggers after 5 failed attempts
- [ ] Session persists across page loads
- [ ] Session expires after timeout

**CSRF Protection**
- [ ] POST/PUT/DELETE requests fail without CSRF token
- [ ] Requests succeed with valid CSRF token
- [ ] Token invalidates after session change

**Password Security**
- [ ] Passwords hashed with Argon2id
- [ ] Cannot login with plaintext password from database
- [ ] Password change requires current password

### 3. Tiles Management

**Create Tile**
- [ ] New tile appears in admin list
- [ ] Required fields validated (slug, title, URL)
- [ ] URL validation rejects invalid URLs
- [ ] Hex color validation works
- [ ] Tile appears on public page when visible=1
- [ ] Hidden tiles (visible=0) don't appear publicly
- [ ] Scheduled tiles don't appear before publish_at date

**Edit Tile**
- [ ] Changes save and reflect immediately
- [ ] Partial updates work (only changed fields)
- [ ] Media can be changed
- [ ] Accent color updates

**Delete Tile**
- [ ] Tile deleted from database
- [ ] Tile removed from public view
- [ ] Order adjusts for remaining tiles
- [ ] Confirmation prompt appears

**Reorder Tiles**
- [ ] Drag-and-drop saves new order
- [ ] Order persists after page reload
- [ ] Order reflects on public page

### 4. Media Management

**Upload Media**
- [ ] Images upload successfully (JPG, PNG, WebP)
- [ ] Large images (>10MB) rejected
- [ ] Invalid file types rejected
- [ ] WebP version created
- [ ] Responsive sizes generated (480, 768, 1080, 1440, 1920)
- [ ] EXIF data stripped
- [ ] Images cropped to 3:4 ratio
- [ ] Rate limiting (20 per hour) enforced

**Serve Media**
- [ ] Images accessible via /api/media/serve/{id}
- [ ] WebP format served when requested
- [ ] Proper cache headers set
- [ ] 404 for non-existent media

**Delete Media**
- [ ] Cannot delete media in use by tiles
- [ ] Unused media deletes successfully
- [ ] Files removed from filesystem
- [ ] Variants also deleted

### 5. Settings

**Update Settings**
- [ ] Site title and description update
- [ ] Brand colors change
- [ ] Autoplay settings work
- [ ] Animation speed updates
- [ ] Changes reflect on public page
- [ ] Export generates valid JSON

### 6. Public Site

**Visual & Interaction**
- [ ] Hero section displays correctly
- [ ] Tiles render with images
- [ ] Hover effects work (tilt, lift)
- [ ] Click expands panel
- [ ] Info drawer slides in
- [ ] Drawer shows title, blurb, CTA
- [ ] CTA link opens target URL
- [ ] Close button works
- [ ] ESC key closes drawer

**Responsive Design**
- [ ] Desktop (>1024px): 4 columns
- [ ] Tablet (768-1024px): 3 columns
- [ ] Mobile (<768px): 1-2 columns
- [ ] Drawer becomes bottom sheet on mobile
- [ ] Touch interactions work
- [ ] No horizontal scroll

**Animations**
- [ ] Page load: panels stagger in
- [ ] Panel expand: smooth transition
- [ ] Drawer: slide animation
- [ ] Respects prefers-reduced-motion
- [ ] No animation jank

**Autoplay**
- [ ] Cycles through panels if enabled
- [ ] Pauses on user interaction
- [ ] Respects autoplay_interval setting
- [ ] Can be disabled in settings

**Accessibility**
- [ ] Keyboard: Tab navigates panels
- [ ] Enter/Space opens panel
- [ ] ESC closes drawer
- [ ] Focus visible
- [ ] ARIA labels present
- [ ] Color contrast meets AA
- [ ] Screen reader friendly

**SEO**
- [ ] Title tag correct
- [ ] Meta description present
- [ ] Open Graph tags present
- [ ] JSON-LD schema valid
- [ ] Semantic HTML
- [ ] Alt text on images

### 7. API Endpoints

**Public Endpoints (no auth)**
- [ ] GET /api/public/tiles returns visible tiles
- [ ] GET /api/health returns status

**Authenticated Endpoints**
- [ ] All require valid session
- [ ] Return 401 when not authenticated
- [ ] CSRF token required for mutations

**Error Handling**
- [ ] 404 for invalid routes
- [ ] 400 for bad requests
- [ ] 500 errors logged
- [ ] JSON responses valid

### 8. Performance

**Lighthouse Scores**
- [ ] Performance: 90+ (desktop), 85+ (mobile)
- [ ] Accessibility: 90+
- [ ] Best Practices: 90+
- [ ] SEO: 100

**Metrics**
- [ ] First Contentful Paint < 1.5s
- [ ] Largest Contentful Paint < 2.5s
- [ ] Time to Interactive < 3.5s
- [ ] Cumulative Layout Shift < 0.1

**Optimizations**
- [ ] Images lazy-loaded
- [ ] WebP served to supporting browsers
- [ ] srcset/sizes for responsive images
- [ ] Critical CSS inlined
- [ ] Static assets cached
- [ ] gzip/brotli compression enabled

### 9. Security Headers

- [ ] X-Frame-Options: DENY
- [ ] X-Content-Type-Options: nosniff
- [ ] Content-Security-Policy present
- [ ] Referrer-Policy set
- [ ] Permissions-Policy set

### 10. Database

**Integrity**
- [ ] Foreign keys enforced
- [ ] Constraints validated
- [ ] Transactions atomic
- [ ] No orphaned records

**Performance**
- [ ] WAL mode enabled
- [ ] Indexes created
- [ ] Queries optimized

### 11. Backup & Recovery

- [ ] Manual backup creates .db file
- [ ] Backups timestamped
- [ ] Old backups auto-deleted (14 days)
- [ ] Restore from backup works
- [ ] Uploads can be backed up

### 12. Error Handling

- [ ] PHP errors logged to file
- [ ] Production: errors hidden from users
- [ ] Development: errors displayed
- [ ] 500 errors return JSON
- [ ] Activity log records actions

## Post-Deployment Tests

### 13. Production Environment

**Web Server**
- [ ] Apache/Nginx configured correctly
- [ ] Document root points to /public
- [ ] Rewrite rules work
- [ ] HTTPS enforced
- [ ] SSL certificate valid

**Performance**
- [ ] CDN configured (if used)
- [ ] Static assets served from CDN
- [ ] Server compression enabled
- [ ] Database performance acceptable

**Monitoring**
- [ ] Error logs checked
- [ ] Activity logs reviewed
- [ ] Disk space monitored
- [ ] /api/health endpoint accessible

### 14. Cross-Browser Testing

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] iOS Safari
- [ ] Android Chrome

### 15. Edge Cases

- [ ] No tiles: shows empty state
- [ ] No media: tiles work without images
- [ ] Very long tile titles: truncated
- [ ] Many tiles (20+): performance acceptable
- [ ] Rapid clicks: no race conditions
- [ ] Simultaneous admin users: no conflicts

## Regression Testing Checklist

Run after any code changes:

- [ ] Login/logout flow
- [ ] Create/edit/delete tile
- [ ] Upload/delete media
- [ ] Reorder tiles
- [ ] Public page loads
- [ ] Animations work
- [ ] Mobile responsive

## Load Testing

- [ ] 100 concurrent users
- [ ] 1000 page views/hour
- [ ] Multiple admin sessions
- [ ] Large file uploads
- [ ] Database under load

## Notes

- Test on staging environment first
- Use realistic test data
- Document any issues found
- Verify fixes before production deployment
