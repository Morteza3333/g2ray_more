using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Media;
using FinAccApp.Data.Repositories;
using FinAccApp.Helpers;
using FinAccApp.Models;

namespace FinAccApp.Views
{
    public partial class InstallmentsView : UserControl
    {
        private readonly InstallmentRepository _installmentRepo;
        private readonly ProjectRepository _projectRepo;
        private readonly ActivityRepository _activityRepo;

        private List<ProjectInstallmentWrapper> _projectList = new List<ProjectInstallmentWrapper>();
        private ProjectInstallmentWrapper _selectedWrapper = null!;
        private Installment _currentInstallment = null!;
        private bool _isUpdatingUI = false;

        public InstallmentsView(
            InstallmentRepository installmentRepo,
            ProjectRepository projectRepo,
            ActivityRepository activityRepo)
        {
            InitializeComponent();
            _installmentRepo = installmentRepo;
            _projectRepo = projectRepo;
            _activityRepo = activityRepo;

            Loaded += InstallmentsView_Loaded;
        }

        private void InstallmentsView_Loaded(object sender, RoutedEventArgs e)
        {
            LoadProjects();
        }

        private void LoadProjects()
        {
            try
            {
                var projects = _projectRepo.GetAll().ToList();
                _projectList = projects.Select(p => new ProjectInstallmentWrapper(p)).ToList();
                LstProjects.ItemsSource = _projectList;
            }
            catch { }
        }

        private void LstProjects_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (LstProjects.SelectedItem is ProjectInstallmentWrapper wrapper)
            {
                _selectedWrapper = wrapper;
                TxtSelectedProjectTitle.Text = wrapper.Project.ProjectTitle;

                _currentInstallment = _installmentRepo.GetByProjectId(wrapper.Project.Id);

                if (_currentInstallment == null)
                {
                    decimal baseAmt = wrapper.Project.ContractAmount / 3;
                    _currentInstallment = new Installment
                    {
                        ProjectId = wrapper.Project.Id,
                        Installment1Amount = Math.Round(baseAmt),
                        Installment1DueDate = JalaliDateHelper.AddDays(wrapper.Project.StartDate, 10),
                        Installment2Amount = Math.Round(baseAmt),
                        Installment2DueDate = JalaliDateHelper.AddDays(wrapper.Project.StartDate, 20),
                        Installment3Amount = Math.Round(wrapper.Project.ContractAmount - (Math.Round(baseAmt) * 2)),
                        Installment3DueDate = wrapper.Project.DeliveryDate
                    };
                    _installmentRepo.Add(_currentInstallment);
                }

                _isUpdatingUI = true;

                InstallmentEditorStack.IsEnabled = true;
                TxtInst1Amount.Text = _currentInstallment.Installment1Amount.ToString("0");
                TxtInst1DueDate.Text = _currentInstallment.Installment1DueDate;
                ChkInst1Paid.IsChecked = _currentInstallment.Installment1Paid;

                TxtInst2Amount.Text = _currentInstallment.Installment2Amount.ToString("0");
                TxtInst2DueDate.Text = _currentInstallment.Installment2DueDate;
                ChkInst2Paid.IsChecked = _currentInstallment.Installment2Paid;

                TxtInst3Amount.Text = _currentInstallment.Installment3Amount.ToString("0");
                TxtInst3DueDate.Text = _currentInstallment.Installment3DueDate;
                ChkInst3Paid.IsChecked = _currentInstallment.Installment3Paid;

                _isUpdatingUI = false;

                RefreshCalculations();
            }
        }

        private void Installment_ValuesChanged(object sender, RoutedEventArgs e)
        {
            if (_isUpdatingUI || _currentInstallment == null) return;
            RefreshCalculations();
        }

        private void Installment_ValuesChanged(object sender, TextChangedEventArgs e)
        {
            if (_isUpdatingUI || _currentInstallment == null) return;
            RefreshCalculations();
        }

        private void RefreshCalculations()
        {
            if (_currentInstallment == null) return;

            decimal.TryParse(TxtInst1Amount.Text, out decimal a1);
            decimal.TryParse(TxtInst2Amount.Text, out decimal a2);
            decimal.TryParse(TxtInst3Amount.Text, out decimal a3);

            decimal total = a1 + a2 + a3;
            decimal received = 0;
            if (ChkInst1Paid.IsChecked == true) received += a1;
            if (ChkInst2Paid.IsChecked == true) received += a2;
            if (ChkInst3Paid.IsChecked == true) received += a3;

            decimal remaining = total - received;
            double pct = total > 0 ? (double)(received / total) * 100 : 0;

            TxtTotalAmount.Text = $"{total:N0} تومان";
            TxtReceivedAmount.Text = $"{received:N0} تومان";
            TxtRemainingAmount.Text = $"{remaining:N0} تومان";

            PrgProgress.Value = pct;
            TxtPercentage.Text = $"{pct:0}%";

            string computedStatus = "در حال انجام";
            if (_selectedWrapper.Project.Status == "لغو شده")
            {
                computedStatus = "لغو شده";
            }
            else if (pct >= 100)
            {
                computedStatus = "پرداخت کامل";
            }
            else if (pct > 0 && pct < 100)
            {
                computedStatus = "معلق";
            }
            else
            {
                computedStatus = "در حال انجام";
            }

            _selectedWrapper.Project.Status = computedStatus;
        }

        private void BtnSaveInstallments_Click(object sender, RoutedEventArgs e)
        {
            if (_currentInstallment == null || _selectedWrapper == null) return;

            decimal.TryParse(TxtInst1Amount.Text, out decimal a1);
            decimal.TryParse(TxtInst2Amount.Text, out decimal a2);
            decimal.TryParse(TxtInst3Amount.Text, out decimal a3);

            _currentInstallment.Installment1Amount = a1;
            _currentInstallment.Installment1DueDate = TxtInst1DueDate.Text.Trim();
            _currentInstallment.Installment1Paid = ChkInst1Paid.IsChecked == true;

            _currentInstallment.Installment2Amount = a2;
            _currentInstallment.Installment2DueDate = TxtInst2DueDate.Text.Trim();
            _currentInstallment.Installment2Paid = ChkInst2Paid.IsChecked == true;

            _currentInstallment.Installment3Amount = a3;
            _currentInstallment.Installment3DueDate = TxtInst3DueDate.Text.Trim();
            _currentInstallment.Installment3Paid = ChkInst3Paid.IsChecked == true;

            try
            {
                _installmentRepo.Update(_currentInstallment);
                _projectRepo.Update(_selectedWrapper.Project);

                _activityRepo.Add(new Activity
                {
                    Description = $"بروزرسانی اقساط مالی پروژه: {_selectedWrapper.Project.ProjectTitle} - درصد تسویه: {PrgProgress.Value:0}%",
                    ShamsiDate = JalaliDateHelper.GetCurrentShamsiDate(),
                    Type = "مالی"
                });

                MessageBox.Show("اقساط مالی با موفقیت ثبت گردید و وضعیت پروژه بروزرسانی شد.", "موفقیت", MessageBoxButton.OK, MessageBoxImage.Information);

                if (Window.GetWindow(this) is MainWindow mw)
                {
                    mw.UpdateNotificationsCount();
                }

                LoadProjects();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در ثبت اقساط: {ex.Message}");
            }
        }
    }

    public class ProjectInstallmentWrapper
    {
        public Project Project { get; }

        public ProjectInstallmentWrapper(Project project)
        {
            Project = project;
        }

        public string ProjectTitle => Project.ProjectTitle;
        public string ClientName => Project.ClientName;
        public string StatusText => Project.Status;

        public Brush StatusColorBrush
        {
            get
            {
                return Project.Status switch
                {
                    "پرداخت کامل" => new SolidColorBrush((Color)ColorConverter.ConvertFromString("#2ECC71")),
                    "معلق" => new SolidColorBrush((Color)ColorConverter.ConvertFromString("#F1C40F")),
                    "لغو شده" => new SolidColorBrush((Color)ColorConverter.ConvertFromString("#E74C3C")),
                    _ => new SolidColorBrush((Color)ColorConverter.ConvertFromString("#4A90E2"))
                };
            }
        }
    }
}