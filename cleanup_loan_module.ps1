$paths = @(
  'app/Http/Controllers/LoanController.php',
  'app/Http/Controllers/InstallmentController.php',
  'app/Http/Requests/StoreLoanRequest.php',
  'app/Http/Requests/UpdateLoanRequest.php',
  'app/Models/Loan.php',
  'app/Traits/LoanTrait.php',
  'database/migrations/2022_06_18_164213_create_loans_table.php',
  'database/migrations/2022_06_25_062808_add_paid_column_to_loans_table.php'
)

foreach ($p in $paths) {
  if (Test-Path -LiteralPath $p) {
    Remove-Item -LiteralPath $p -Force
  }
}

$dirs = @(
  'resources/views/pages/transaction/loan',
  'resources/views/pages/transaction/installment'
)

foreach ($d in $dirs) {
  if (Test-Path -LiteralPath $d) {
    Remove-Item -LiteralPath $d -Recurse -Force
  }
}