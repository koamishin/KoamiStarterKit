---
paths:
    - 'scripts/**'
---

# Scripts

## Write git patches as UTF-8 without BOM on Windows

On Windows PowerShell 5.1, the `>` redirect writes UTF-16LE, which git cannot parse as a patch. When saving `git diff` output for `git apply`, write UTF-8 without BOM via [System.IO.File]::WriteAllLines().
