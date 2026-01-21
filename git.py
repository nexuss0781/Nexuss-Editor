#!/usr/bin/env python3

import os
import subprocess
import sys
import re

def run(cmd, capture=False, fail_msg=None):
    try:
        if capture:
            return subprocess.check_output(cmd, shell=True, text=True).strip()
        subprocess.check_call(cmd, shell=True)
    except subprocess.CalledProcessError:
        if fail_msg:
            sys.exit(fail_msg)
        raise

def exists(cmd):
    return subprocess.call(
        cmd,
        shell=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    ) == 0

def ensure_safe_directory():
    path = os.getcwd()
    run(f'git config --global --add safe.directory "{path}"')

def valid_repo_url(url):
    return re.match(r"^https://github.com/.+/.+(.git)?$", url)

# 0. Fix Termux ownership issue early
ensure_safe_directory()

# 1. Init git
if not os.path.isdir(".git"):
    print("Git not initialized.")
    if input("Initialize git here? [Y/n]: ").strip().lower() == "n":
        sys.exit("Aborted.")
    run("git init")
    run("git branch -m main")

# 2. Remote
if not exists("git remote get-url origin"):
    print("No git remote configured.")
    mode = input("Add existing repo or create new? [add/new]: ").strip().lower()

    if mode == "add":
        repo_url = input("Enter repository URL: ").strip()

        if not valid_repo_url(repo_url):
            sys.exit("Invalid GitHub repository URL.")

        run(f"git remote add origin {repo_url}", fail_msg="Failed to add remote.")

    elif mode == "new":
        name = input("Enter new repository name: ").strip()
        visibility = input("Private or public? [private/public]: ").strip().lower()
        flag = "--private" if visibility == "private" else "--public"

        run(
            f"gh repo create {name} {flag} --source=. --remote=origin --push=false",
            fail_msg="GitHub repo creation failed."
        )

    else:
        sys.exit("Invalid option.")
else:
    print("Git remote already configured.")

# 3. Commit
run("git add .")

msg = input("Commit message [initial commit]: ").strip() or "initial commit"
run(f'git commit -m "{msg}"', fail_msg="Commit failed.")

# 4. Push
branch = run("git branch --show-current", capture=True) or "main"
run(f"git push -u origin {branch}", fail_msg="Push failed.")

print("Done.")
