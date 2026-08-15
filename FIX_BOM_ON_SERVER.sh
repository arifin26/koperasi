# Copy this command and run it on your Linux server:

cd /home/karyadev-koperasi/htdocs/koperasi.karyadev.com

# Fix BOM in InterestPostMonthly.php
sed -i "1s/^\xEF\xBB\xBF//" app/Console/Commands/InterestPostMonthly.php

# Fix BOM in other modified files (just to be safe)
sed -i "1s/^\xEF\xBB\xBF//" app/Console/Commands/InterestCalculateDaily.php
sed -i "1s/^\xEF\xBB\xBF//" app/Http/Controllers/Auth/LoginController.php
sed -i "1s/^\xEF\xBB\xBF//" app/Models/Deposit.php
sed -i "1s/^\xEF\xBB\xBF//" app/Models/WorkdayYearCount.php

# Verify BOM is removed
echo "Checking first file..."
if head -c 3 app/Console/Commands/InterestPostMonthly.php | od -An -tx1 | grep -q "ef bb bf"; then
    echo "ERROR: BOM still present!"
else
    echo "SUCCESS: BOM removed! You can now run: php artisan migrate"
fi
