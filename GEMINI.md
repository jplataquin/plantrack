# Project Instructions

## Git & Repository Operations

### Pushing Changes to Remote
When pushing commits to GitHub in this environment:
- The Linux/WSL user environment stores credentials in `~/.git-credentials` using the `store` credential helper.
- Non-interactive subshell runs often clear or omit interactive credential prompting (`credential.helper=`).
- Always pass `-c credential.helper=store` when executing `git push`:
  ```bash
  # Push current branch directly to remote main:
  git -c credential.helper=store push origin HEAD:main

  # Push to master tracking branch:
  git -c credential.helper=store push origin master
  ```

## Testing & Quality Assurance
- Run tests via `php artisan test`.
- All feature and unit tests must pass before pushing changes.
