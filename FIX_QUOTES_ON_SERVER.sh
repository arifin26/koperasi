# Run these commands on your Linux server to fix the quote syntax error:

cd /home/karyadev-koperasi/htdocs/koperasi.karyadev.com

# Backup the file first
cp app/Console/Commands/InterestCalculateDaily.php app/Console/Commands/InterestCalculateDaily.php.backup

# Fix the quotes using sed
sed -i "s/DB::table(\"deposits\")/DB::table('deposits')/g" app/Console/Commands/InterestCalculateDaily.php
sed -i "s/type=\"sukarela\"/type='sukarela'/g" app/Console/Commands/InterestCalculateDaily.php
sed -i "s/type=\"wajib\"/type='wajib'/g" app/Console/Commands/InterestCalculateDaily.php
sed -i "s/type=\"penarikan\"/type='penarikan'/g" app/Console/Commands/InterestCalculateDaily.php
sed -i "s/->where(\"customer_id\"/->where('customer_id'/g" app/Console/Commands/InterestCalculateDaily.php
sed -i "s/->where(\"transaction_date\", \"<=/->where('transaction_date', '<=/g" app/Console/Commands/InterestCalculateDaily.php

# Verify the fix by checking line 81
sed -n '81p' app/Console/Commands/InterestCalculateDaily.php

echo "Fix applied! Now run: php artisan migrate"
