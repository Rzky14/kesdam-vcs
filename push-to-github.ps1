# PowerShell script to commit and push to feature/document-management

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "COMMITTING AND PUSHING TO GITHUB" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check current status
Write-Host "1. Checking git status..." -ForegroundColor Yellow
git status

Write-Host ""
Write-Host "2. Adding all changes..." -ForegroundColor Yellow
git add .

Write-Host ""
Write-Host "3. Creating commit..." -ForegroundColor Yellow
git commit -m "Implement multi-level document approval workflow with KAUR, KASI, and PIMPINAN roles

Features:
- Add Document model methods: approve(), reject(), requestCorrection()
- Add DocumentController approval action methods
- Update DocumentPolicy with multi-level authorization
- Add approval routes (approve, reject, request-correction)
- Create approval UI with modals in document show page
- Add approval level indicators in document list
- Separate KAUR, KASI, PIMPINAN roles in RolePermissionSeeder
- Add ApprovalUsersSeeder for test users
- Fix approval history field names (action_date, comment)
- Improve approval success messages with next approver info"

Write-Host ""
Write-Host "4. Pushing to feature/document-management..." -ForegroundColor Yellow
git push origin HEAD:feature/document-management --force

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "DONE! Check GitHub now." -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
