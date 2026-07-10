using System;
using System.IO;
using Microsoft.Data.Sqlite;

namespace FinAccApp.Data
{
    public static class DatabaseInitializer
    {
        private static string DbPath => Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "finance.db");
        public static string ConnectionString => $"Data Source={DbPath}";

        public static void Initialize()
        {
            using (var connection = new SqliteConnection(ConnectionString))
            {
                connection.Open();

                string createUsersTable = @"
                    CREATE TABLE IF NOT EXISTS Users (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        Username TEXT UNIQUE NOT NULL,
                        PasswordHash TEXT NOT NULL,
                        Role TEXT NOT NULL,
                        Permissions TEXT
                    );";
                using (var cmd = new SqliteCommand(createUsersTable, connection)) cmd.ExecuteNonQuery();

                string checkAdmin = "SELECT COUNT(*) FROM Users WHERE Username = 'admin';";
                using (var checkCmd = new SqliteCommand(checkAdmin, connection))
                {
                    long count = (long)checkCmd.ExecuteScalar()!;
                    if (count == 0)
                    {
                        string seedAdmin = "INSERT INTO Users (Username, PasswordHash, Role, Permissions) VALUES ('admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 'Admin', 'All');";
                        using (var seedCmd = new SqliteCommand(seedAdmin, connection)) seedCmd.ExecuteNonQuery();
                    }
                }

                string createSettingsTable = @"
                    CREATE TABLE IF NOT EXISTS Settings (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        CompanyName TEXT,
                        LogoPath TEXT,
                        Theme TEXT,
                        Currency TEXT,
                        TaxPercentage REAL,
                        DefaultCategoriesJson TEXT,
                        AutomaticBackup INTEGER,
                        LastBackupDate TEXT
                    );";
                using (var cmd = new SqliteCommand(createSettingsTable, connection)) cmd.ExecuteNonQuery();

                string checkSettings = "SELECT COUNT(*) FROM Settings;";
                using (var checkCmd = new SqliteCommand(checkSettings, connection))
                {
                    long count = (long)checkCmd.ExecuteScalar()!;
                    if (count == 0)
                    {
                        string seedSettings = "INSERT INTO Settings (CompanyName, LogoPath, Theme, Currency, TaxPercentage, DefaultCategoriesJson, AutomaticBackup, LastBackupDate) " +
                                              "VALUES ('استودیو طراحی وب و دیجیتال مارکتینگ شمس', '', 'Light', 'تومان', 9, '[]', 1, '');";
                        using (var seedCmd = new SqliteCommand(seedSettings, connection)) seedCmd.ExecuteNonQuery();
                    }
                }

                string createProjectsTable = @"
                    CREATE TABLE IF NOT EXISTS Projects (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ClientName TEXT,
                        PhoneNumber TEXT,
                        CompanyName TEXT,
                        ProjectTitle TEXT,
                        Description TEXT,
                        ContractAmount REAL,
                        ProjectCost REAL,
                        StartDate TEXT,
                        DeliveryDate TEXT,
                        Status TEXT,
                        Priority TEXT,
                        Notes TEXT,
                        Files TEXT,
                        InvoiceNumber TEXT,
                        PaymentMethod TEXT,
                        ProjectCategory TEXT,
                        ProjectColorLabel TEXT,
                        IsPinned INTEGER DEFAULT 0,
                        IsFavorite INTEGER DEFAULT 0
                    );";
                using (var cmd = new SqliteCommand(createProjectsTable, connection)) cmd.ExecuteNonQuery();

                string createInstallmentsTable = @"
                    CREATE TABLE IF NOT EXISTS Installments (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ProjectId INTEGER,
                        Installment1Amount REAL,
                        Installment1DueDate TEXT,
                        Installment1Paid INTEGER,
                        Installment2Amount REAL,
                        Installment2DueDate TEXT,
                        Installment2Paid INTEGER,
                        Installment3Amount REAL,
                        Installment3DueDate TEXT,
                        Installment3Paid INTEGER,
                        FOREIGN KEY(ProjectId) REFERENCES Projects(Id) ON DELETE CASCADE
                    );";
                using (var cmd = new SqliteCommand(createInstallmentsTable, connection)) cmd.ExecuteNonQuery();

                string createIncomesTable = @"
                    CREATE TABLE IF NOT EXISTS Incomes (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        IncomeDate TEXT,
                        Category TEXT,
                        Client TEXT,
                        Project TEXT,
                        Amount REAL,
                        PaymentMethod TEXT,
                        Description TEXT,
                        Attachment TEXT,
                        IsPersonal INTEGER DEFAULT 0
                    );";
                using (var cmd = new SqliteCommand(createIncomesTable, connection)) cmd.ExecuteNonQuery();

                string createExpensesTable = @"
                    CREATE TABLE IF NOT EXISTS Expenses (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ExpenseDate TEXT,
                        Category TEXT,
                        Amount REAL,
                        Description TEXT,
                        ReceiptAttachment TEXT,
                        IsPersonal INTEGER DEFAULT 0
                    );";
                using (var cmd = new SqliteCommand(createExpensesTable, connection)) cmd.ExecuteNonQuery();

                string createPersonalFinanceTable = @"
                    CREATE TABLE IF NOT EXISTS PersonalFinance (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        Savings REAL,
                        Investments REAL,
                        Loans REAL,
                        CreditCard REAL,
                        Wallet REAL,
                        Cash REAL,
                        BankAccountsJson TEXT
                    );";
                using (var cmd = new SqliteCommand(createPersonalFinanceTable, connection)) cmd.ExecuteNonQuery();

                string checkPersonalFinance = "SELECT COUNT(*) FROM PersonalFinance;";
                using (var checkCmd = new SqliteCommand(checkPersonalFinance, connection))
                {
                    long count = (long)checkCmd.ExecuteScalar()!;
                    if (count == 0)
                    {
                        string seedPersonalFinance = "INSERT INTO PersonalFinance (Savings, Investments, Loans, CreditCard, Wallet, Cash, BankAccountsJson) VALUES (0,0,0,0,0,0,'[]');";
                        using (var seedCmd = new SqliteCommand(seedPersonalFinance, connection)) seedCmd.ExecuteNonQuery();
                    }
                }

                string createClientsTable = @"
                    CREATE TABLE IF NOT EXISTS Clients (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ClientName TEXT,
                        Phone TEXT,
                        Email TEXT,
                        Address TEXT,
                        Company TEXT,
                        Projects TEXT,
                        TotalPaid REAL,
                        RemainingBalance REAL,
                        Notes TEXT
                    );";
                using (var cmd = new SqliteCommand(createClientsTable, connection)) cmd.ExecuteNonQuery();

                string createInvoicesTable = @"
                    CREATE TABLE IF NOT EXISTS Invoices (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        InvoiceNumber TEXT,
                        ShamsiDate TEXT,
                        ClientName TEXT,
                        ClientPhone TEXT,
                        ItemsJson TEXT,
                        Discount REAL,
                        TaxPercentage REAL,
                        Total REAL,
                        Remaining REAL,
                        QRCodePath TEXT
                    );";
                using (var cmd = new SqliteCommand(createInvoicesTable, connection)) cmd.ExecuteNonQuery();

                string createActivitiesTable = @"
                    CREATE TABLE IF NOT EXISTS Activities (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ShamsiDate TEXT,
                        Description TEXT,
                        Type TEXT
                    );";
                using (var cmd = new SqliteCommand(createActivitiesTable, connection)) cmd.ExecuteNonQuery();

                string createNotificationsTable = @"
                    CREATE TABLE IF NOT EXISTS Notifications (
                        Id INTEGER PRIMARY KEY AUTOINCREMENT,
                        Title TEXT,
                        Description TEXT,
                        ShamsiDate TEXT,
                        IsRead INTEGER DEFAULT 0,
                        Type TEXT
                    );";
                using (var cmd = new SqliteCommand(createNotificationsTable, connection)) cmd.ExecuteNonQuery();
            }
        }
    }
}