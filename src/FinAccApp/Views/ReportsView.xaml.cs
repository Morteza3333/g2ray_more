using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data.Repositories;
using FinAccApp.Helpers;
using FinAccApp.Models;
using FinAccApp.Services;
using Microsoft.Win32;

namespace FinAccApp.Views
{
    public partial class ReportsView : UserControl
    {
        private readonly ProjectRepository _projectRepo;
        private readonly IncomeRepository _incomeRepo;
        private readonly ExpenseRepository _expenseRepo;
        private readonly ClientRepository _clientRepo;
        private readonly InstallmentRepository _installmentRepo;

        private readonly PdfExportService _pdfService = new PdfExportService();
        private readonly ExcelExportService _excelService = new ExcelExportService();

        public ReportsView(
            ProjectRepository projectRepo,
            IncomeRepository incomeRepo,
            ExpenseRepository expenseRepo,
            ClientRepository clientRepo,
            InstallmentRepository installmentRepo)
        {
            InitializeComponent();
            _projectRepo = projectRepo;
            _incomeRepo = incomeRepo;
            _expenseRepo = expenseRepo;
            _clientRepo = clientRepo;
            _installmentRepo = installmentRepo;

            Loaded += ReportsView_Loaded;
        }

        private void ReportsView_Loaded(object sender, RoutedEventArgs e)
        {
            RefreshReport();
        }

        private void CboReportType_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            RefreshReport();
        }

        private void CboPeriod_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            RefreshReport();
        }

        private void RefreshReport()
        {
            if (CboReportType == null || CboPeriod == null || DgrReports == null) return;

            string type = (CboReportType.SelectedItem as ComboBoxItem)?.Tag?.ToString() ?? "Income";
            string period = (CboPeriod.SelectedItem as ComboBoxItem)?.Tag?.ToString() ?? "All";

            try
            {
                if (type == "Income")
                {
                    TxtReportTitle.Text = "گزارش درآمدهای استودیو شمس";
                    var data = _incomeRepo.GetAll().Where(x => FilterByPeriod(x.IncomeDate, period)).ToList();
                    DgrReports.ItemsSource = data.Select(x => new
                    {
                        کد = x.Id,
                        تاریخ = x.IncomeDate,
                        دسته‌بندی = x.Category,
                        مشتری = x.Client,
                        پروژه = x.Project,
                        مبلغ_تومان = x.Amount,
                        روش_پرداخت = x.PaymentMethod,
                        توضیحات = x.Description
                    }).ToList();
                }
                else if (type == "Expense")
                {
                    TxtReportTitle.Text = "گزارش هزینه‌های عمومی و دفتری";
                    var data = _expenseRepo.GetAll().Where(x => FilterByPeriod(x.ExpenseDate, period)).ToList();
                    DgrReports.ItemsSource = data.Select(x => new
                    {
                        کد = x.Id,
                        تاریخ = x.ExpenseDate,
                        دسته‌بندی = x.Category,
                        مبلغ_تومان = x.Amount,
                        توضیحات = x.Description
                    }).ToList();
                }
                else if (type == "Profit")
                {
                    TxtReportTitle.Text = "گزارش سودآوری پروژه‌ها";
                    var data = _projectRepo.GetAll().Where(x => FilterByPeriod(x.StartDate, period)).ToList();
                    DgrReports.ItemsSource = data.Select(x => new
                    {
                        کد = x.Id,
                        عنوان_پروژه = x.ProjectTitle,
                        مشتری = x.ClientName,
                        مبلغ_قرارداد = x.ContractAmount,
                        هزینه‌ها = x.ProjectCost,
                        سود_خالص = x.Profit,
                        تاریخ_شروع = x.StartDate,
                        تاریخ_تحویل = x.DeliveryDate,
                        وضعیت = x.Status
                    }).ToList();
                }
                else if (type == "Installment")
                {
                    TxtReportTitle.Text = "گزارش حساب اقساط سه‌گانه";
                    var projects = _projectRepo.GetAll().ToList();
                    var list = new List<object>();
                    foreach (var p in projects)
                    {
                        var inst = _installmentRepo.GetByProjectId(p.Id);
                        if (inst != null)
                        {
                            list.Add(new
                            {
                                کد_پروژه = p.Id,
                                عنوان_پروژه = p.ProjectTitle,
                                مشتری = p.ClientName,
                                مبلغ_کل = inst.TotalAmount,
                                دریافت_شده = inst.ReceivedAmount,
                                مانده_طلب = inst.RemainingAmount,
                                درصد_تسویه = $"{inst.PaymentPercentage:0}%"
                            });
                        }
                    }
                    DgrReports.ItemsSource = list;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در بارگذاری گزارش: {ex.Message}");
            }
        }

        private bool FilterByPeriod(string dateStr, string period)
        {
            if (period == "All" || string.IsNullOrEmpty(dateStr)) return true;

            string today = JalaliDateHelper.GetCurrentShamsiDate();
            int diff = JalaliDateHelper.GetDaysDifference(dateStr, today);

            return period switch
            {
                "Daily" => diff == 0,
                "Weekly" => diff >= 0 && diff <= 7,
                "Monthly" => diff >= 0 && diff <= 30,
                "Yearly" => diff >= 0 && diff <= 365,
                _ => true
            };
        }

        private void BtnExportExcel_Click(object sender, RoutedEventArgs e)
        {
            if (DgrReports.ItemsSource == null) return;

            var sfd = new SaveFileDialog
            {
                Filter = "Excel Files (*.xlsx)|*.xlsx",
                FileName = $"Report_{DateTime.Now:yyyyMMdd_HHmmss}.xlsx"
            };

            if (sfd.ShowDialog() == true)
            {
                try
                {
                    string sheetName = "گزارش مالی";
                    var headers = GetHeaders();
                    var rows = GetRowsData();

                    _excelService.ExportDataToExcel(sheetName, headers, rows, sfd.FileName);
                    MessageBox.Show("گزارش با موفقیت به صورت اکسل صادر و ذخیره شد.", "صادرات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"خطا در صادرات اکسل: {ex.Message}");
                }
            }
        }

        private void BtnExportPdf_Click(object sender, RoutedEventArgs e)
        {
            if (DgrReports.ItemsSource == null) return;

            var sfd = new SaveFileDialog
            {
                Filter = "PDF Files (*.pdf)|*.pdf",
                FileName = $"Report_{DateTime.Now:yyyyMMdd_HHmmss}.pdf"
            };

            if (sfd.ShowDialog() == true)
            {
                try
                {
                    string title = TxtReportTitle.Text;
                    var headersList = new List<string[]> { GetHeaders() };
                    var dataList = GetRowsData();

                    _pdfService.ExportReportToPdf(title, headersList, dataList, sfd.FileName);
                    MessageBox.Show("گزارش با موفقیت به صورت فایل PDF صادر و ذخیره شد.", "صادرات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"خطا در صادرات پی‌دی‌اف: {ex.Message}");
                }
            }
        }

        private string[] GetHeaders()
        {
            var list = new List<string>();
            var source = DgrReports.ItemsSource;
            if (source != null)
            {
                var first = source.Cast<object>().FirstOrDefault();
                if (first != null)
                {
                    var props = first.GetType().GetProperties();
                    foreach (var p in props) list.Add(p.Name.Replace("_", " "));
                }
            }
            return list.ToArray();
        }

        private List<string[]> GetRowsData()
        {
            var list = new List<string[]>();
            var source = DgrReports.ItemsSource;
            if (source != null)
            {
                foreach (var item in source)
                {
                    var props = item.GetType().GetProperties();
                    var rowList = new List<string>();
                    foreach (var p in props)
                    {
                        var val = p.GetValue(item);
                        rowList.Add(val?.ToString() ?? string.Empty);
                    }
                    list.Add(rowList.ToArray());
                }
            }
            return list;
        }
    }
}