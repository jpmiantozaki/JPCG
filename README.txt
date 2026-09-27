Upload only public/admin_diag.php into the public/ folder of your existing JPCG repository.
Wait for Render to redeploy, then run:
Invoke-RestMethod -Uri "https://jpcg.onrender.com/admin_diag.php" -Headers $headers
The endpoint does not reveal either secret. Delete admin_diag.php after troubleshooting.
