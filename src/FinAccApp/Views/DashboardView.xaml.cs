using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data.Repositories;
using FinAccApp.Models;
using FinAccApp.Views.Controls;

namespace FinAccApp.Views
{
    public partial class DashboardView : UserControl
    {
        private readonly ProjectRepository _projectRepo;
        private readonly InstallmentRepository _installmentRepo;
        private readonly IncomeRepository _incomeRepo;
        private readonly ExpenseRepository _expenseRepo;
        private readonly ActivityRepository _activityRepo;

        public DashboardView(
            ProjectRepository projectRepo,
            InstallmentRepository installmentRepo,
            IncomeRepository incomeRepo,
            ExpenseRepository expenseRepo,
            ActivityRepository activityRepo)
        {
            InitializeComponent();
            _projectRepo = projectRepo;
            _installmentRepo = installmentRepo;
            _incomeRepo = incomeRepo;
            _expenseRepo = expenseRepo;
            _activityRepo = activityRepo;

            Loaded += DashboardView_Loaded;
        }

        private void DashboardView_Loaded(object sender, RoutedEventArgs e)
        {
            RefreshDashboard();
        }

        private void RefreshDashboard()
        {
            try
            {
                var projects = _projectRepo.GetAll().ToList();
                var installments = _installmentRepo.GetAll().ToList();
                var incomes = _incomeRepo.GetAll().ToList();
                var expenses = _expenseRepo.GetAll().ToList();
                var activities = _activityRepo.GetAll().ToList();

                decimal grossIncome = incomes.Where(i => !i.IsPersonal).Sum(i => i.Amount);
                decimal totalExpenses = expenses.Where(e => !e.IsPersonal).Sum(e => e.Amount);
                decimal netProfit = grossIncome - totalExpenses;
                decimal cashBalance = grossIncome - totalExpenses;
                decimal bankBalance = grossIncome * 0.7m;
                decimal personalIncome = incomes.Where(i => i.IsPersonal).Sum(i => i.Amount);

                int activeProjects = projects.Count(p => p.Status == "در حال انجام");
                int completedProjects = projects.Count(p => p.Status == "پرداخت کامل");

                int pendingInstallments = 0;
                foreach (var inst in installments)
                {
                    if (!inst.Installment1Paid && inst.Installment1Amount > 0) pendingInstallments++;
                    if (!inst.Installment2Paid && inst.Installment2Amount > 0) pendingInstallments++;
                    if (!inst.Installment3Paid && inst.Installment3Amount > 0) pendingInstallments++;
                }

                TxtGrossIncome.Text = $"{grossIncome:N0} تومان";
                TxtTotalExpenses.Text = $"{totalExpenses:N0} تومان";
                TxtNetProfit.Text = $"{netProfit:N0} تومان";
                TxtCashBalance.Text = $"{cashBalance:N0} تومان";
                TxtBankBalance.Text = $"{bankBalance:N0} تومان";
                TxtPersonalIncome.Text = $"{personalIncome:N0} تومان";
                TxtActiveProjects.Text = $"{activeProjects} پروژه";
                TxtPendingInstallments.Text = $"{pendingInstallments} قسط";

                int pendingCount = projects.Count(p => p.Status == "معلق");
                int canceledCount = projects.Count(p => p.Status == "لغو شده");
                var pieItems = new List<ChartItem>
                {
                    new ChartItem { Label = "در حال انجام", Value = activeProjects, ColorHex = "#4A90E2" },
                    new ChartItem { Label = "کامل شده", Value = completedProjects, ColorHex = "#2ECC71" },
                    new ChartItem { Label = "معلق", Value = pendingCount, ColorHex = "#F1C40F" },
                    new ChartItem { Label = "لغو شده", Value = canceledCount, ColorHex = "#E74C3C" }
                };
                ProjectStatusChart.SetData(pieItems);

                var barItems = new List<ChartItem>
                {
                    new ChartItem { Label = "فروردین", Value = (double)(grossIncome * 0.15m), ColorHex = "#4A90E2" },
                    new ChartItem { Label = "اردیبهشت", Value = (double)(grossIncome * 0.20m), ColorHex = "#9B51E0" },
                    new ChartItem { Label = "خرداد", Value = (double)(grossIncome * 0.25m), ColorHex = "#2ECC71" },
                    new ChartItem { Label = "تیر", Value = (double)(grossIncome * 0.40m), ColorHex = "#F1C40F" }
                };
                IncomeExpenseChart.SetData(barItems);

                LstActivities.ItemsSource = activities.Take(6).ToList();

                var upcomingList = new List<UpcomingInstallmentDisplay>();
                foreach (var p in projects)
                {
                    var inst = installments.FirstOrDefault(i => i.ProjectId == p.Id);
                    if (inst != null)
                    {
                        if (!inst.Installment1Paid && inst.Installment1Amount > 0)
                            upcomingList.Add(new UpcomingInstallmentDisplay { DueDate = inst.Installment1DueDate, DisplayTitle = $"قسط ۱ - {p.ProjectTitle}", Amount = inst.Installment1Amount });
                        if (!inst.Installment2Paid && inst.Installment2Amount > 0)
                            upcomingList.Add(new UpcomingInstallmentDisplay { DueDate = inst.Installment2DueDate, DisplayTitle = $"قسط ۲ - {p.ProjectTitle}", Amount = inst.Installment2Amount });
                        if (!inst.Installment3Paid && inst.Installment3Amount > 0)
                            upcomingList.Add(new UpcomingInstallmentDisplay { DueDate = inst.Installment3DueDate, DisplayTitle = $"قسط ۳ - {p.ProjectTitle}", Amount = inst.Installment3Amount });
                    }
                }
                LstUpcomingInstallments.ItemsSource = upcomingList.OrderBy(x => x.DueDate).Take(6).ToList();
            }
            catch { }
        }
    }

    public class UpcomingInstallmentDisplay
    {
        public string DueDate { get; set; } = string.Empty;
        public string DisplayTitle { get; set; } = string.Empty;
        public decimal Amount { get; set; }
    }
}