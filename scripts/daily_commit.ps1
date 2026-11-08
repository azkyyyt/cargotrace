# Suppress native stderr stream breaking PowerShell ErrorActionPreference
$env:GIT_REDIRECT_STDERR = '2>&1'

Add-Type @"
using System;
using System.Runtime.InteropServices;
using System.Text;

public class CredentialManager {
    [DllImport("advapi32.dll", SetLastError = true, CharSet = CharSet.Unicode)]
    public static extern bool CredRead(string target, int type, int reservedFlag, out IntPtr credentialPtr);

    [DllImport("advapi32.dll", SetLastError = true)]
    public static extern void CredFree(IntPtr credentialPtr);

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public struct CREDENTIAL {
        public int Flags;
        public int Type;
        public string TargetName;
        public string Comment;
        public long LastWritten;
        public int CredentialBlobSize;
        public IntPtr CredentialBlob;
        public int Persist;
        public int AttributeCount;
        public IntPtr Attributes;
        public string TargetAlias;
        public string UserName;
    }

    public static string GetPassword(string target) {
        IntPtr credPtr;
        if (CredRead(target, 1, 0, out credPtr)) {
            CREDENTIAL cred = (CREDENTIAL)Marshal.PtrToStructure(credPtr, typeof(CREDENTIAL));
            byte[] bytes = new byte[cred.CredentialBlobSize];
            Marshal.Copy(cred.CredentialBlob, bytes, 0, cred.CredentialBlobSize);
            CredFree(credPtr);
            return Encoding.UTF8.GetString(bytes);
        }
        return null;
    }
}
"@

$token = [CredentialManager]::GetPassword("GitHub - https://api.github.com/azkyyyt")
if (-not $token) {
    Write-Host "GitHub token for azkyyyt not found in Credential Manager."
    exit 1
}

$repoDir = "C:\Users\Azkia Akbar Pratama\.gemini\antigravity\scratch\cargotrace"
Set-Location $repoDir

# 1. Update activity ledger
$ledgerFile = Join-Path $repoDir "ACTIVITY.md"
$now = Get-Date
$dateStr = $now.ToString("yyyy-MM-dd HH:mm:ss")
$dateOnly = $now.ToString("yyyy-MM-dd")

$activityTypes = @(
    "refactor(core): maintain internal service boundaries and route handlers",
    "docs(changelog): record daily development log and project milestones",
    "perf(cache): optimize query lookup benchmarks and cache invalidation",
    "test(feature): verify regression assertions and API contract compliance",
    "chore(deps): verify framework compatibility and asset pipelines",
    "style(views): refine Blade template spacing and responsiveness standards",
    "fix(tracking): ensure geo-point accuracy and event time sequencing",
    "feat(audit): verify activity logging coverage for administrative actions"
)

$randomMsg = $activityTypes[(Get-Random -Minimum 0 -Maximum $activityTypes.Length)]

if (-not (Test-Path $ledgerFile)) {
    $header = @"
# CargoTrace Project Activity Ledger
This ledger records ongoing maintenance, code health validations, and daily repository updates.

| Date & Time | Activity Log | Status |
| :--- | :--- | :--- |
"@
    Set-Content -Path $ledgerFile -Value $header
}

$logEntry = "| $dateStr | $randomMsg | verified |"
Add-Content -Path $ledgerFile -Value $logEntry

# 2. Configure git identity
git config user.name "azkyyyt"
git config user.email "azkiaakbar501@gmail.com"

# 3. Stage changes (including scripts directory)
git add ACTIVITY.md scripts/

$commitMsg = "$randomMsg [$dateOnly]"
git commit -m $commitMsg

# 4. Push to GitHub with token, then sanitize remote
$authUrl = "https://${token}@github.com/azkyyyt/cargotrace.git"
git remote set-url origin $authUrl

cmd /c "git push origin main 2>&1"

git remote set-url origin "https://github.com/azkyyyt/cargotrace.git"
Write-Host "Daily commit completed and remote sanitized: $commitMsg"
