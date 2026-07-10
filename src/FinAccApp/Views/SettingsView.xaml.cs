using System;
using System.IO;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data;
using Microsoft.Win32;
using Microsoft.Data.Sqlite;

namespace FinAccApp.Views
{
    public partial class SettingsView : UserControl
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;
        private static string DbPath => Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "finance.db");

        public SettingsView()
        {
            InitializeComponent();
            Loaded += SettingsView_Loaded;
        }

        private void SettingsView_Loaded(object sender, RoutedEventArgs e)
        {
            LoadSettings();
        }

        private void LoadSettings()
        {
            try
            {
                using (var conn = new SqliteConnection(_connectionString))
                {
                    conn.Open();
                    string query = "SELECT * FROM Settings LIMIT 1;";
                    using (var cmd = new SqliteCommand(query, conn))
                    using (var reader = cmd.ExecuteReader())
                    {
                        if (reader.Read())
                        {
                            TxtCompany.Text = reader["CompanyName"]?.ToString() ?? string.Empty;
                            TxtCurrency.Text = reader["Currency"]?.ToString() ?? "تومان";
                            TxtTax.Text = reader["TaxPercentage"]?.ToString() ?? "9";
                            ChkAutoBackup.IsChecked = Convert.ToInt32(reader["AutomaticBackup"]) == 1;

                            string lastDate = reader["LastBackupDate"]?.ToString() ?? string.Empty;
                            if (!string.IsNullOrEmpty(lastDate))
                            {
                                TxtBackupPath.Text = $"آخرین پشتیبان‌گیری در تاریخ: {lastDate}";
                            }
                        }
                    }
                }
            }
            catch { }
        }

        private void BtnSaveSettings_Click(object sender, RoutedEventArgs e)
        {
            if (string.IsNullOrWhiteSpace(TxtCompany.Text))
            {
                MessageBox.Show("لطفاً نام شرکت را وارد نمایید.", "خطا", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            decimal.TryParse(TxtTax.Text, out decimal tax);

            try
            {
                using (var conn = new SqliteConnection(_connectionString))
                {
                    conn.Open();
                    string query = @"
                        UPDATE Settings SET
                            CompanyName = @CompanyName, Currency = @Currency,
                            TaxPercentage = @Tax, AutomaticBackup = @AutoBackup
                        WHERE Id = 1;";
                    using (var cmd = new SqliteCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@CompanyName", TxtCompany.Text.Trim());
                        cmd.Parameters.AddWithValue("@Currency", TxtCurrency.Text.Trim());
                        cmd.Parameters.AddWithValue("@Tax", tax);
                        cmd.Parameters.AddWithValue("@AutoBackup", ChkAutoBackup.IsChecked == true ? 1 : 0);
                        cmd.ExecuteNonQuery();
                    }
                }

                if (Window.GetWindow(this) is MainWindow mw)
                {
                    mw.CompanyName = TxtCompany.Text.Trim();
                }

                MessageBox.Show("تنظیمات عمومی با موفقیت ثبت و ذخیره گردید.", "عملیات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در ثبت تنظیمات: {ex.Message}");
            }
        }

        private void BtnBackup_Click(object sender, RoutedEventArgs e)
        {
            var sfd = new SaveFileDialog
            {
                Filter = "Database Backup Files (*.bak)|*.bak",
                FileName = $"FinanceBackup_{DateTime.Now:yyyyMMdd}.bak"
            };

            if (sfd.ShowDialog() == true)
            {
                try
                {
                    if (File.Exists(DbPath))
                    {
                        File.Copy(DbPath, sfd.FileName, true);

                        string today = Helpers.JalaliDateHelper.GetCurrentShamsiDate();
                        using (var conn = new SqliteConnection(_connectionString))
                        {
                            conn.Open();
                            string query = "UPDATE Settings SET LastBackupDate = @Date WHERE Id = 1;";
                            using (var cmd = new SqliteCommand(query, conn))
                            {
                                cmd.Parameters.AddWithValue("@Date", today);
                                cmd.ExecuteNonQuery();
                            }
                        }

                        TxtBackupPath.Text = $"آخرین پشتیبان‌گیری در تاریخ: {today}";
                        MessageBox.Show("تهیه نسخه پشتیبان با موفقیت کامل شد.", "عملیات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"خطا در فایل پشتیبان: {ex.Message}");
                }
            }
        }

        private void BtnRestore_Click(object sender, RoutedEventArgs e)
        {
            var ofd = new OpenFileDialog
            {
                Filter = "Database Backup Files (*.bak)|*.bak"
            };

            if (ofd.ShowDialog() == true)
            {
                var res = MessageBox.Show("هشدار: بازیابی پایگاه‌داده باعث بازنویسی اطلاعات فعلی شما خواهد شد. آیا مطمئن هستید؟", "بازیابی اطلاعات", MessageBoxButton.YesNo, MessageBoxImage.Warning);
                if (res == MessageBoxResult.Yes)
                {
                    try
                    {
                        if (File.Exists(ofd.FileName))
                        {
                            SqliteConnection.ClearAllPools();
                            File.Copy(ofd.FileName, DbPath, true);

                            MessageBox.Show("بازیابی پایگاه‌داده با موفقیت انجام شد. لطفا برنامه را مجددا راه‌اندازی کنید.", "عملیات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
                        }
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"خطا در بازیابی پایگاه‌داده: {ex.Message}");
                    }
                }
            }
        }
    }
}