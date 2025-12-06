# GitHub Copilot Instructions for KESDAM VCS Project

## **Mandatory Rules**

### **1. Always Read Project Documentation**
- **ALWAYS** read `docs/PRD.md` before starting any task
- **ALWAYS** read `docs/plan.md` to check which tasks are pending
- Understand the context, requirements, and project goals before implementing features
- Reference PRD sections when making design decisions

### **2. Task Management**
- **ONLY** check/mark a task as completed when the author explicitly approves and confirms the task
- **NEVER** mark a task as done without explicit author confirmation
- Always reference the task ID (e.g., `[FOUND-001]`, `[USER-002]`) in commits and communications
- Keep task status updated in `docs/plan.md` after author approval

### **3. SOLID Principles**
Always adhere to SOLID principles for folder structure and code:

#### **S - Single Responsibility Principle**
- Each class should have only one reason to change
- Separate concerns into different classes/files
- Example: Separate business logic from database operations

#### **O - Open/Closed Principle**
- Classes should be open for extension but closed for modification
- Use interfaces and abstract classes for extensibility
- Avoid modifying existing working code; extend instead

#### **L - Liskov Substitution Principle**
- Derived classes must be substitutable for their base classes
- Maintain consistent behavior in inheritance hierarchies
- Don't break contracts established by parent classes

#### **I - Interface Segregation Principle**
- Many client-specific interfaces are better than one general-purpose interface
- Don't force classes to implement interfaces they don't use
- Keep interfaces focused and minimal

#### **D - Dependency Inversion Principle**
- Depend on abstractions, not concretions
- Use dependency injection
- High-level modules should not depend on low-level modules

### **4. Laravel Best Practices**
- Follow Laravel conventions and directory structure
- Use Eloquent ORM properly with relationships
- Implement Repository Pattern for database operations
- Use Service Classes for business logic
- Use Form Requests for validation
- Use Resource Classes for API responses
- Use Events and Listeners for decoupled operations
- Use Jobs and Queues for long-running tasks
- Use Policies for authorization logic
- Use Middleware for request filtering

### **5. Code Organization**

#### **Folder Structure**
```
app/
├── Http/
│   ├── Controllers/          # Handle HTTP requests
│   ├── Middleware/           # Request filtering
│   ├── Requests/             # Form validation
│   └── Resources/            # API response formatting
├── Models/                   # Eloquent models
├── Repositories/             # Data access layer
├── Services/                 # Business logic
├── Policies/                 # Authorization logic
├── Events/                   # Event classes
├── Listeners/                # Event handlers
├── Jobs/                     # Queue jobs
├── Mail/                     # Mailable classes
├── Notifications/            # Notification classes
└── Helpers/                  # Helper functions
```

#### **Naming Conventions**
- Controllers: `{Resource}Controller.php` (e.g., `UserController.php`)
- Models: Singular, PascalCase (e.g., `User.php`, `Schedule.php`)
- Repositories: `{Model}Repository.php` (e.g., `UserRepository.php`)
- Services: `{Function}Service.php` (e.g., `AuthenticationService.php`)
- Requests: `{Action}{Model}Request.php` (e.g., `StoreUserRequest.php`)
- Resources: `{Model}Resource.php` (e.g., `UserResource.php`)
- Policies: `{Model}Policy.php` (e.g., `DocumentPolicy.php`)
- Jobs: `{Action}{Resource}Job.php` (e.g., `ProcessDocumentJob.php`)
- Events: `{Past-tense-verb}{Resource}Event.php` (e.g., `UserCreated.php`)
- Listeners: `{Action}{Event}Listener.php` (e.g., `SendWelcomeEmail.php`)

### **6. Database Guidelines**
- Use migrations for all database changes
- Use seeders for initial/test data
- Use factories for test data generation
- Always add indexes for frequently queried columns
- Use proper foreign key constraints
- Use soft deletes where appropriate
- Add timestamps to all tables
- Use UUID for sensitive records if needed

### **7. Security Requirements**
- **NEVER** expose sensitive data in responses
- **ALWAYS** validate and sanitize user input
- Use Laravel's built-in CSRF protection
- Implement proper authentication and authorization
- Hash passwords using bcrypt/argon2
- Encrypt sensitive data (especially classified documents)
- Use HTTPS in production
- Implement rate limiting on API endpoints
- Log security events
- Follow OWASP security guidelines

### **8. Testing Requirements**
- Write tests BEFORE or alongside feature implementation
- Maintain minimum 80% code coverage
- Write unit tests for all business logic
- Write feature tests for all user workflows
- Use factories and seeders in tests
- Mock external dependencies
- Test edge cases and error scenarios
- Run tests before committing code

### **9. Documentation Requirements**
- Add PHPDoc comments to all classes and methods
- Document complex logic with inline comments
- Update API documentation when adding/modifying endpoints
- Keep README.md updated with setup instructions
- Document environment variables in `.env.example`
- Add comments for non-obvious code
- Document security considerations

### **10. Git Workflow**
- Create feature branches from main: `feature/<feature-name>`
- Use conventional commit messages: `[TASK-ID] type: description`
- Keep commits atomic and focused
- Write meaningful commit messages
- Pull request must include:
  - Task ID reference
  - Description of changes
  - Testing performed
  - Screenshots (if UI changes)
- Get code review before merging
- Ensure all tests pass before merging
- Squash commits when merging (if needed)

### **11. Performance Guidelines**
- Use eager loading to avoid N+1 queries
- Cache frequently accessed data
- Optimize database queries
- Use indexes appropriately
- Paginate large result sets
- Optimize file uploads and storage
- Use queues for time-consuming operations
- Monitor and log slow queries

### **12. Error Handling**
- Use try-catch blocks appropriately
- Log errors with context
- Return meaningful error messages to users
- Don't expose sensitive information in errors
- Use Laravel's exception handler
- Create custom exceptions when needed
- Validate input at multiple layers

### **13. Code Review Checklist**
Before submitting code for review, ensure:
- [ ] Code follows SOLID principles
- [ ] Laravel best practices are followed
- [ ] All tests pass
- [ ] Code is properly documented
- [ ] No security vulnerabilities
- [ ] No performance issues
- [ ] Error handling is implemented
- [ ] Validation is in place
- [ ] Code is DRY (Don't Repeat Yourself)
- [ ] Naming conventions are followed
- [ ] Database migrations are reversible
- [ ] Environment variables are documented

### **14. Communication Guidelines**
- Ask for clarification if requirements are unclear
- Provide status updates on long-running tasks
- Document decisions and rationale
- Report blockers immediately
- Suggest improvements when appropriate
- Be respectful and professional
- Keep stakeholders informed

## **Project-Specific Rules**

### **KESDAM VCS Specific Guidelines**

#### **User Roles**
The system has 4 main user roles with different permissions:
1. **Admin Sistem**: Full system access
2. **Pimpinan/Pejabat Tinggi**: Approval authority, view access
3. **Kasi/Kaur**: Mid-level approval, data verification
4. **Batih/Staf**: Data entry, operational tasks

#### **Document Classification**
- **Biasa**: Regular documents
- **Rahasia**: Classified documents (requires encryption)
- **Telegram**: Urgent communications

#### **Schedule Types**
- **Jadwal Dukkes**: Health support schedules
- **Jadwal Jaga**: Duty/guard schedules
- **Jadwal Kegiatan Satuan**: Unit activity schedules

#### **Approval Workflow**
- Multi-level approval chain: Staf → Kaur → Kasi → Pimpinan
- Support for corrections and resubmissions
- Manual signature upload capability
- Complete audit trail

#### **Security Priorities**
- Encryption for classified documents (Surat Rahasia)
- Role-based access control (RBAC)
- Audit trail for all operations
- Secure file storage
- Automated backups

## **Quick Reference**

### **Before Starting Any Task**
1. Read PRD.md
2. Read plan.md
3. Check current task status
4. Understand requirements
5. Plan implementation following SOLID principles

### **During Implementation**
1. Write tests first (TDD approach)
2. Follow Laravel conventions
3. Implement SOLID principles
4. Add proper documentation
5. Handle errors appropriately
6. Consider security implications

### **After Implementation**
1. Run all tests
2. Check code coverage
3. Review code against checklist
4. Update documentation
5. Request code review
6. Wait for author approval before marking task complete

## **Remember**
> Quality over speed. Clean, maintainable, secure code is more important than quick delivery.
> Always think about the end users (military personnel) and their needs.
> Follow the principle: "Make it work, make it right, make it fast" - in that order.

---

**Last Updated:** October 30, 2025  
**Project Version:** 1.0  
**Status:** Active Development
