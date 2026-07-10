using System;

namespace FinAccApp.Models
{
    public class User
    {
        public int Id { get; set; }
        public string Username { get; set; } = string.Empty;
        public string PasswordHash { get; set; } = string.Empty;
        public string Role { get; set; } = "User";
        public string Permissions { get; set; } = string.Empty;
    }

    public class Project
    {
        public int Id { get; set; }
        public string ClientName { get; set; } = string.Empty;
        public string PhoneNumber { get; set; } = string.Empty;
        public string CompanyName { get; set; } = string.Empty;
        public string ProjectTitle { get; set; } = string.Empty;
        public string Description { get; set; } = string.Empty;
        public decimal ContractAmount { get; set; }
        public decimal ProjectCost { get; set; }
        public decimal Profit => ContractAmount - ProjectCost;
        public string StartDate { get; set; } = string.Empty;
        public string DeliveryDate { get; set; } = string.Empty;
        public string Status { get; set; } = "در حال انجام";
        public string Priority { get; set; } = "متوسط";
        public string Notes { get; set; } = string.Empty;
        public string Files { get; set; } = string.Empty;
        public string InvoiceNumber { get; set; } = string.Empty;
        public string PaymentMethod { get; set; } = "نقدی";
        public string ProjectCategory { get; set; } = "طراحی سایت";
        public string ProjectColorLabel { get; set; } = "#4A90E2";
        public bool IsPinned { get; set; } = false;
        public bool IsFavorite { get; set; } = false;
    }

    public class Installment
    {
        public int Id { get; set; }
        public int ProjectId { get; set; }

        public decimal Installment1Amount { get; set; }
        public string Installment1DueDate { get; set; } = string.Empty;
        public bool Installment1Paid { get; set; }

        public decimal Installment2Amount { get; set; }
        public string Installment2DueDate { get; set; } = string.Empty;
        public bool Installment2Paid { get; set; }

        public decimal Installment3Amount { get; set; }
        public string Installment3DueDate { get; set; } = string.Empty;
        public bool Installment3Paid { get; set; }

        public decimal ReceivedAmount => (Installment1Paid ? Installment1Amount : 0) +
                                         (Installment2Paid ? Installment2Amount : 0) +
                                         (Installment3Paid ? Installment3Amount : 0);

        public decimal TotalAmount => Installment1Amount + Installment2Amount + Installment3Amount;
        public decimal RemainingAmount => TotalAmount - ReceivedAmount;
        public double PaymentPercentage => TotalAmount > 0 ? (double)(ReceivedAmount / TotalAmount) * 100 : 0;
    }

    public class Income
    {
        public int Id { get; set; }
        public string IncomeDate { get; set; } = string.Empty;
        public string Category { get; set; } = "درآمد پروژه";
        public string Client { get; set; } = string.Empty;
        public string Project { get; set; } = string.Empty;
        public decimal Amount { get; set; }
        public string PaymentMethod { get; set; } = "نقدی";
        public string Description { get; set; } = string.Empty;
        public string Attachment { get; set; } = string.Empty;
        public bool IsPersonal { get; set; } = false;
    }

    public class Expense
    {
        public int Id { get; set; }
        public string ExpenseDate { get; set; } = string.Empty;
        public string Category { get; set; } = "سایر";
        public decimal Amount { get; set; }
        public string Description { get; set; } = string.Empty;
        public string ReceiptAttachment { get; set; } = string.Empty;
        public bool IsPersonal { get; set; } = false;
    }

    public class PersonalFinance
    {
        public int Id { get; set; }
        public decimal Savings { get; set; }
        public decimal Investments { get; set; }
        public decimal Loans { get; set; }
        public decimal CreditCard { get; set; }
        public decimal Wallet { get; set; }
        public decimal Cash { get; set; }
        public string BankAccountsJson { get; set; } = "[]";
    }

    public class Client
    {
        public int Id { get; set; }
        public string ClientName { get; set; } = string.Empty;
        public string Phone { get; set; } = string.Empty;
        public string Email { get; set; } = string.Empty;
        public string Address { get; set; } = string.Empty;
        public string Company { get; set; } = string.Empty;
        public string Projects { get; set; } = string.Empty;
        public decimal TotalPaid { get; set; }
        public decimal RemainingBalance { get; set; }
        public string Notes { get; set; } = string.Empty;
    }

    public class Invoice
    {
        public int Id { get; set; }
        public string InvoiceNumber { get; set; } = string.Empty;
        public string ShamsiDate { get; set; } = string.Empty;
        public string ClientName { get; set; } = string.Empty;
        public string ClientPhone { get; set; } = string.Empty;
        public string ItemsJson { get; set; } = "[]";
        public decimal Discount { get; set; }
        public decimal TaxPercentage { get; set; }
        public decimal Total { get; set; }
        public decimal Remaining { get; set; }
        public string QRCodePath { get; set; } = string.Empty;
    }

    public class Activity
    {
        public int Id { get; set; }
        public string ShamsiDate { get; set; } = string.Empty;
        public string Description { get; set; } = string.Empty;
        public string Type { get; set; } = "عمومی";
    }

    public class Notification
    {
        public int Id { get; set; }
        public string Title { get; set; } = string.Empty;
        public string Description { get; set; } = string.Empty;
        public string ShamsiDate { get; set; } = string.Empty;
        public bool IsRead { get; set; } = false;
        public string Type { get; set; } = "اطلاع‌رسانی";
    }

    public class Settings
    {
        public int Id { get; set; }
        public string CompanyName { get; set; } = "استودیو طراحی وب و دیجیتال مارکتینگ";
        public string LogoPath { get; set; } = string.Empty;
        public string Theme { get; set; } = "Light";
        public string Currency { get; set; } = "تومان";
        public decimal TaxPercentage { get; set; } = 9;
        public string DefaultCategoriesJson { get; set; } = "[]";
        public bool AutomaticBackup { get; set; } = true;
        public string LastBackupDate { get; set; } = string.Empty;
    }
}