using System;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data;
using FinAccApp.Data.Repositories;
using Microsoft.Data.Sqlite;

namespace FinAccApp.Views
{
    public partial class PersonalFinanceView : UserControl
    {
        private readonly IncomeRepository _incomeRepo;

        private readonly ExpenseRepository _expenseRepo;
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public PersonalFinanceView(IncomeRepository incomeRepo, ExpenseRepository expenseRepo)
        {
            InitializeComponent();
            _incomeRepo = incomeRepo;
            _expenseRepo = expenseRepo;

            Loaded += PersonalFinanceView_Loaded;
        }

        private void PersonalFinanceView_Loaded(object sender, RoutedEventArgs e)
        {
            LoadPersonalData();
        }

        private void LoadPersonalData()
        {
            try
            {
                decimal personalIncomes = _incomeRepo.GetAll().Where(i => i.IsPersonal).Sum(i => i.Amount);
                decimal personalExpenses = _expenseRepo.GetAll().Where(e => e.IsPersonal).Sum(e => e.Amount);

                TxtPersonalIncome.Text = $"{personalIncomes:N0} تومان";
                TxtPersonalExpense.Text = $"{personalExpenses:N0} تومان";

                using (var conn = new SqliteConnection(_connectionString))
                {
                    conn.Open();
                    string query = "SELECT * FROM PersonalFinance LIMIT 1;";
                    using (var cmd = new SqliteCommand(query, conn))
                    using (var reader = cmd.ExecuteReader())
                    {
                        if (reader.Read())
                        {
                            decimal savings = Convert.ToDecimal(reader["Savings"]);
                            decimal investments = Convert.ToDecimal(reader["Investments"]);
                            decimal loans = Convert.ToDecimal(reader["Loans"]);
                            decimal creditCard = Convert.ToDecimal(reader["CreditCard"]);
                            decimal wallet = Convert.ToDecimal(reader["Wallet"]);
                            decimal cash = Convert.ToDecimal(reader["Cash"]);

                            TxtSavings.Text = $"{savings:N0} تومان";
                            TxtInvestments.Text = $"{investments:N0} تومان";
                            TxtLoans.Text = $"{loans:N0} تومان";
                            TxtCreditCard.Text = $"{creditCard:N0} تومان";
                            TxtWallet.Text = $"{wallet:N0} تومان";
                            TxtCash.Text = $"{cash:N0} تومان";

                            InpSavings.Text = savings.ToString("0");
                            InpInvestments.Text = investments.ToString("0");
                            InpLoans.Text = loans.ToString("0");
                            InpCreditCard.Text = creditCard.ToString("0");
                            InpWallet.Text = wallet.ToString("0");
                            InpCash.Text = cash.ToString("0");
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در دریافت دارایی‌های شخصی: {ex.Message}");
            }
        }

        private void BtnSave_Click(object sender, RoutedEventArgs e)
        {
            decimal.TryParse(InpSavings.Text, out decimal savings);
            decimal.TryParse(InpInvestments.Text, out decimal investments);
            decimal.TryParse(InpLoans.Text, out decimal loans);
            decimal.TryParse(InpCreditCard.Text, out decimal creditCard);
            decimal.TryParse(InpWallet.Text, out decimal wallet);
            decimal.TryParse(InpCash.Text, out decimal cash);

            try
            {
                using (var conn = new SqliteConnection(_connectionString))
                {
                    conn.Open();
                    string query = @"
                        UPDATE PersonalFinance SET
                            Savings = @Savings, Investments = @Investments, Loans = @Loans,
                            CreditCard = @CreditCard, Wallet = @Wallet, Cash = @Cash
                        WHERE Id = 1;";
                    using (var cmd = new SqliteCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@Savings", savings);
                        cmd.Parameters.AddWithValue("@Investments", investments);
                        cmd.Parameters.AddWithValue("@Loans", loans);
                        cmd.Parameters.AddWithValue("@CreditCard", creditCard);
                        cmd.Parameters.AddWithValue("@Wallet", wallet);
                        cmd.Parameters.AddWithValue("@Cash", cash);
                        cmd.ExecuteNonQuery();
                    }
                }

                MessageBox.Show("ترازنامه مالی شخصی با موفقیت ویرایش گردید.", "عملیات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
                LoadPersonalData();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در ذخیره‌سازی اطلاعات: {ex.Message}");
            }
        }
    }
}