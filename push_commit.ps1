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
    Write-Error "Token not found"
    exit 1
}

$repoName = "cargotrace"
$authUrl = "https://${token}@github.com/azkyyyt/${repoName}.git"

Set-Location "C:\xampp\htdocs\cargotrace"
git add .
git commit -m "feat(ui): add visual atmospheric weather cards with animated clouds, lightning, and rain" 2>&1
git remote set-url origin $authUrl
$res1 = git push origin main 2>&1
git remote set-url origin "https://github.com/azkyyyt/${repoName}.git"
Write-Output "XAMPP push result: $res1"

Set-Location "C:\Users\Azkia Akbar Pratama\.gemini\antigravity\scratch\cargotrace"
git add .
git commit -m "feat(ui): add visual atmospheric weather cards with animated clouds, lightning, and rain" 2>&1
git remote set-url origin $authUrl
$res2 = git push origin main 2>&1
git remote set-url origin "https://github.com/azkyyyt/${repoName}.git"
Write-Output "Scratch push result: $res2"
