using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text.Json;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data.Repositories;
using FinAccApp.Helpers;
using FinAccApp.Models;
using FinAccApp.Services;
using Microsoft.Win32;

namespace FinAccApp.Views
{
    public partial class InvoicesView : UserControl, ISearchable
    {
        private readonly InvoiceRepository _invoiceRepo;
        private readonly PdfExportService _pdfService = new PdfExportService();

        private List<Invoice> _allInvoices = new List<Invoice>();
        private Invoice _selectedInvoice = null!;

        public InvoicesView(InvoiceRepository invoiceRepo)
        {
            InitializeComponent();
            _invoiceRepo = invoiceRepo;

            Loaded += InvoicesView_Loaded;
        }

        private void InvoicesView_Loaded(object sender, RoutedEventArgs e)
        {
            LoadInvoices();
            ResetForm();
        }

        private void LoadInvoices()
        {
            try
            {
                _allInvoices = _invoiceRepo.GetAll().ToList();
                LstInvoices.ItemsSource = _allInvoices;
            }
            catch { }
        }

        private void ResetForm()
        {
            _selectedInvoice = null!;
            TxtInvoiceNum.Text = "INV-" + new Random().Next(10000, 99999);
            TxtDate.Text = JalaliDateHelper.GetCurrentShamsiDate();
            TxtClient.Text = string.Empty;
            TxtPhone.Text = string.Empty;
            TxtItemsInput.Text = "پشتیبانی سالانه هاست و دامنه, 1, 4500000\r\nطراحی لوگوی استودیو, 1, 8000000";
            TxtDiscount.Text = "0";
            TxtTaxPercent.Text = "9";
            CalculateInvoiceTotals(null!, null!);
        }

        private void LstInvoices_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (LstInvoices.SelectedItem is Invoice inv)
            {
                _selectedInvoice = inv;
                TxtInvoiceNum.Text = inv.InvoiceNumber;
                TxtDate.Text = inv.ShamsiDate;
                TxtClient.Text = inv.ClientName;
                TxtPhone.Text = inv.ClientPhone;
                TxtDiscount.Text = inv.Discount.ToString("0");
                TxtTaxPercent.Text = inv.TaxPercentage.ToString("0");

                try
                {
                    var items = JsonSerializer.Deserialize<List<InvoiceItem>>(inv.ItemsJson);
                    if (items != null)
                    {
                        var lines = items.Select(x => $"{x.Description}, {x.Quantity}, {x.UnitPrice}");
                        TxtItemsInput.Text = string.Join("\r\n", lines);
                    }
                }
                catch { }

                CalculateInvoiceTotals(null!, null!);
            }
        }

        private void CalculateInvoiceTotals(object sender, TextChangedEventArgs e)
        {
            if (LblFinalTotal == null) return;

            decimal total = CalculateBaseTotal();
            decimal.TryParse(TxtDiscount.Text, out decimal discount);
            decimal.TryParse(TxtTaxPercent.Text, out decimal taxPercent);

            decimal discountedTotal = Math.Max(0, total - discount);
            decimal taxAmount = discountedTotal * (taxPercent / 100);
            decimal finalTotal = discountedTotal + taxAmount;

            LblFinalTotal.Text = $"{finalTotal:N0} تومان";
        }

        private decimal CalculateBaseTotal()
        {
            decimal total = 0;
            string text = TxtItemsInput.Text;
            if (string.IsNullOrWhiteSpace(text)) return 0;

            string[] lines = text.Split(new[] { '\r', '\n', '\n' }, StringSplitOptions.RemoveEmptyEntries);
            foreach (var line in lines)
            {
                string[] parts = line.Split(',');
                if (parts.Length >= 3)
                {
                    int.TryParse(parts[1].Trim(), out int qty);
                    decimal.TryParse(parts[2].Trim(), out decimal price);
                    total += qty * price;
                }
            }
            return total;
        }

        private List<InvoiceItem> ParseItems()
        {
            var list = new List<InvoiceItem>();
            string text = TxtItemsInput.Text;
            if (string.IsNullOrWhiteSpace(text)) return list;

            string[] lines = text.Split(new[] { '\r', '\n', '\n' }, StringSplitOptions.RemoveEmptyEntries);
            foreach (var line in lines)
            {
                string[] parts = line.Split(',');
                if (parts.Length >= 3)
                {
                    int.TryParse(parts[1].Trim(), out int qty);
                    decimal.TryParse(parts[2].Trim(), out decimal price);

                    list.Add(new InvoiceItem
                    {
                        Description = parts[0].Trim(),
                        Quantity = qty,
                        UnitPrice = price
                    });
                }
            }
            return list;
        }

        private void BtnNew_Click(object sender, RoutedEventArgs e)
        {
            ResetForm();
            LstInvoices.SelectedItem = null;
        }

        private void BtnSave_Click(object sender, RoutedEventArgs e)
        {
            if (string.IsNullOrWhiteSpace(TxtClient.Text) || string.IsNullOrWhiteSpace(TxtInvoiceNum.Text))
            {
                MessageBox.Show("لطفاً شماره فاکتور و نام کارفرما را تکمیل کنید.", "خطا", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            var items = ParseItems();
            if (items.Count == 0)
            {
                MessageBox.Show("لطفاً حداقل یک آیتم در بخش اقلام فاکتور وارد کنید.", "خطا", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            decimal baseTotal = CalculateBaseTotal();
            decimal.TryParse(TxtDiscount.Text, out decimal discount);
            decimal.TryParse(TxtTaxPercent.Text, out decimal taxPercent);

            decimal discountedTotal = Math.Max(0, baseTotal - discount);
            decimal taxAmount = discountedTotal * (taxPercent / 100);
            decimal finalTotal = discountedTotal + taxAmount;

            bool isNew = (_selectedInvoice == null);
            if (isNew)
            {
                _selectedInvoice = new Invoice();
            }

            _selectedInvoice.InvoiceNumber = TxtInvoiceNum.Text.Trim();
            _selectedInvoice.ShamsiDate = TxtDate.Text.Trim();
            _selectedInvoice.ClientName = TxtClient.Text.Trim();
            _selectedInvoice.ClientPhone = TxtPhone.Text.Trim();
            _selectedInvoice.ItemsJson = JsonSerializer.Serialize(items);
            _selectedInvoice.Discount = discount;
            _selectedInvoice.TaxPercentage = taxPercent;
            _selectedInvoice.Total = finalTotal;
            _selectedInvoice.Remaining = finalTotal;

            try
            {
                if (isNew) _invoiceRepo.Add(_selectedInvoice);
                else _invoiceRepo.Update(_selectedInvoice);

                MessageBox.Show("فاکتور با موفقیت در پایگاه‌داده محلی ثبت گردید.", "عملیات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
                LoadInvoices();
                ResetForm();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در ذخیره‌سازی فاکتور: {ex.Message}");
            }
        }

        private void BtnExportPdf_Click(object sender, RoutedEventArgs e)
        {
            if (_selectedInvoice == null)
            {
                MessageBox.Show("لطفاً ابتدا یک فاکتور از لیست سمت راست انتخاب یا ذخیره کنید.", "راهنما", MessageBoxButton.OK, MessageBoxImage.Information);
                return;
            }

            var sfd = new SaveFileDialog
            {
                Filter = "PDF Files (*.pdf)|*.pdf",
                FileName = $"Invoice_{_selectedInvoice.InvoiceNumber}.pdf"
            };

            if (sfd.ShowDialog() == true)
            {
                try
                {
                    _pdfService.ExportInvoiceToPdf(_selectedInvoice, sfd.FileName);
                    MessageBox.Show("فایل PDF فاکتور با موفقیت صادر و ذخیره گردید.", "خروجی موفقیت‌آمیز", MessageBoxButton.OK, MessageBoxImage.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"خطا در صدور فایل پی‌دی‌اف فاکتور: {ex.Message}");
                }
            }
        }

        public void PerformSearch(string query)
        {
            query = query.ToLower();
            var filtered = _allInvoices.Where(i =>
                i.InvoiceNumber.ToLower().Contains(query) ||
                i.ClientName.ToLower().Contains(query) ||
                i.ShamsiDate.Contains(query)
            ).ToList();

            LstInvoices.ItemsSource = filtered;
        }
    }
}