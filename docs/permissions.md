# Roles and permissions

> The brief requires a final review of this matrix, the access review process
> and offboarding (brief section 3). Until that review, the assignments below
> are a starting point.

## Roles

| Role (group) | Who |
| --- | --- |
| Super Administrator (`superadmin`) | Technical owners. Manage staff accounts, roles and settings |
| Ministry Administrator (`ministryadmin`) | Central Ministry administrators |
| Department Editor (`editor`) | Staff who publish for one or more departments |
| Read-Only Staff (`readonly`) | Staff who need to see the administration area only |

New accounts start as Read-Only Staff.

## Matrix

| Permission | Super Admin | Ministry Admin | Dept. Editor | Read-Only |
| --- | :-: | :-: | :-: | :-: |
| `admin.access`: open the administration area | ✓ | ✓ | ✓ | ✓ |
| `users.manage`: staff accounts and roles | ✓ | | | |
| `settings.manage`: system settings | ✓ | | | |
| `audit.view`: audit log | ✓ | ✓ | | |
| `content.view`: all content, including drafts | ✓ | ✓ | ✓ | ✓ |
| `content.publish-department`: publish in own departments | | | ✓ | |
| `content.publish-any`: publish in any department | | ✓ | | |
| `content.restore`: restore a retained version | | ✓ | | |
| `missions.manage`: mission records | | ✓ | | |
| `missions.publish-activities`: mission activities | | ✓ | | |
| `inquiries.view` / `inquiries.manage`: Public Inquiries Inbox | | ✓ | | |
| `diaspora.submissions-view` / `-manage`: Diaspora inbox | per person | per person | per person | |

## Rules behind the matrix

- **Direct publishing.** Editors publish within their scope without a second
  approval (brief section 3).
- **Several departments.** An editor's scope is the set of departments in
  `department_user`. Membership is managed with `service('departmentAccess')->assign()`
  and `->remove()`.
- **Mission activities** are published only by central Ministry administrators
  (brief sections 2 and 3). Department membership does not change this.
- **Diaspora content** is published by editors who belong to a department marked
  `is_diaspora`.
- **Diaspora submissions** contain personal data. No role grants access to them,
  not even Super Administrator (accepted 26 October 2026). Access is granted to
  named people:

  ```php
  $user = auth()->getProvider()->findByCredentials(['email' => 'person@example.invalid']);
  $user->addPermission('diaspora.submissions-view');
  ```

  A screen for this comes with the Diaspora inbox at Gate 4. Every grant and
  removal will be written to the audit log.

## Open points for the final review

- Should Super Administrators be able to publish content and mission activities?
  They currently cannot.
- Who restores earlier versions besides Ministry Administrators?
- Access review frequency and the offboarding procedure.
