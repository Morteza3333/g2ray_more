using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data.Repositories;
using FinAccApp.Helpers;
using FinAccApp.Models;

namespace FinAccApp.Views
{
    public partial class ProjectsView : UserControl, ISearchable
    {
        private readonly ProjectRepository _projectRepo;
        private readonly InstallmentRepository _installmentRepo;
        private readonly ClientRepository _clientRepo;
        private readonly ActivityRepository _activityRepo;

        private List<Project> _allProjects = new List<Project>();
        private Project _selectedProject = null!;

        public ProjectsView(
            ProjectRepository projectRepo,
            InstallmentRepository installmentRepo,
            ClientRepository clientRepo,
            ActivityRepository activityRepo)
        {
            InitializeComponent();
            _projectRepo = projectRepo;
            _installmentRepo = installmentRepo;
            _clientRepo = clientRepo;
            _activityRepo = activityRepo;

            Loaded += ProjectsView_Loaded;
        }

        private void ProjectsView_Loaded(object sender, RoutedEventArgs e)
        {
            LoadProjects();
            ResetForm();
        }

        private void LoadProjects()
        {
            try
            {
                _allProjects = _projectRepo.GetAll().ToList();
                LstProjects.ItemsSource = _allProjects;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در دریافت لیست پروژه‌ها: {ex.Message}");
            }
        }

        private void ResetForm()
        {
            _selectedProject = null!;
            TxtId.Text = "جدید (پس از ذخیره)";
            TxtTitle.Text = string.Empty;
            TxtClientName.Text = string.Empty;
            TxtPhoneNumber.Text = string.Empty;
            TxtCompanyName.Text = string.Empty;
            CboCategory.SelectedIndex = 0;
            TxtContractAmount.Text = "0";
            TxtProjectCost.Text = "0";
            TxtProfit.Text = "0";
            TxtStartDate.Text = JalaliDateHelper.GetCurrentShamsiDate();
            TxtDeliveryDate.Text = JalaliDateHelper.AddDays(JalaliDateHelper.GetCurrentShamsiDate(), 30);
            CboStatus.SelectedIndex = 0;
            CboPriority.SelectedIndex = 1;
            CboColor.SelectedIndex = 0;
            TxtDescription.Text = string.Empty;
            TxtNotes.Text = string.Empty;
            TxtFiles.Text = string.Empty;
            TxtFileLabel.Text = "فایل پیوست یا قرارداد را به این جعبه بکشید...";
        }

        private void LstProjects_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (LstProjects.SelectedItem is Project project)
            {
                _selectedProject = project;
                TxtId.Text = project.Id.ToString();
                TxtTitle.Text = project.ProjectTitle;
                TxtClientName.Text = project.ClientName;
                TxtPhoneNumber.Text = project.PhoneNumber;
                TxtCompanyName.Text = project.CompanyName;

                SelectComboByContent(CboCategory, project.ProjectCategory);
                TxtContractAmount.Text = project.ContractAmount.ToString("0");
                TxtProjectCost.Text = project.ProjectCost.ToString("0");
                TxtProfit.Text = project.Profit.ToString("N0");
                TxtStartDate.Text = project.StartDate;
                TxtDeliveryDate.Text = project.DeliveryDate;

                SelectComboByContent(CboStatus, project.Status);
                SelectComboByContent(CboPriority, project.Priority);
                SelectComboByTag(CboColor, project.ProjectColorLabel);

                TxtDescription.Text = project.Description;
                TxtNotes.Text = project.Notes;
                TxtFiles.Text = project.Files;

                if (!string.IsNullOrEmpty(project.Files))
                {
                    TxtFileLabel.Text = Path.GetFileName(project.Files);
                }
                else
                {
                    TxtFileLabel.Text = "فایل پیوست یا قرارداد را به این جعبه بکشید...";
                }
            }
        }

        private void CalculateProfit(object sender, TextChangedEventArgs e)
        {
            if (TxtContractAmount == null || TxtProjectCost == null || TxtProfit == null) return;

            decimal.TryParse(TxtContractAmount.Text, out decimal contract);
            decimal.TryParse(TxtProjectCost.Text, out decimal cost);

            TxtProfit.Text = (contract - cost).ToString("N0");
        }

        private void BtnNew_Click(object sender, RoutedEventArgs e)
        {
            ResetForm();
            LstProjects.SelectedItem = null;
        }

        private void BtnSave_Click(object sender, RoutedEventArgs e)
        {
            if (string.IsNullOrWhiteSpace(TxtTitle.Text) || string.IsNullOrWhiteSpace(TxtClientName.Text))
            {
                MessageBox.Show("لطفاً عنوان پروژه و نام مشتری را وارد نمایید.", "خطای ورود داده", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            decimal.TryParse(TxtContractAmount.Text, out decimal contractAmt);
            decimal.TryParse(TxtProjectCost.Text, out decimal projectCost);

            string status = (CboStatus.SelectedItem as ComboBoxItem)?.Content.ToString() ?? "در حال انجام";
            string priority = (CboPriority.SelectedItem as ComboBoxItem)?.Content.ToString() ?? "متوسط";
            string category = (CboCategory.SelectedItem as ComboBoxItem)?.Content.ToString() ?? "طراحی سایت";
            string color = (CboColor.SelectedItem as ComboBoxItem)?.Tag?.ToString() ?? "#4A90E2";

            bool isNew = (_selectedProject == null);

            if (isNew)
            {
                _selectedProject = new Project();
            }

            _selectedProject.ProjectTitle = TxtTitle.Text.Trim();
            _selectedProject.ClientName = TxtClientName.Text.Trim();
            _selectedProject.PhoneNumber = TxtPhoneNumber.Text.Trim();
            _selectedProject.CompanyName = TxtCompanyName.Text.Trim();
            _selectedProject.ProjectCategory = category;
            _selectedProject.ContractAmount = contractAmt;
            _selectedProject.ProjectCost = projectCost;
            _selectedProject.StartDate = TxtStartDate.Text.Trim();
            _selectedProject.DeliveryDate = TxtDeliveryDate.Text.Trim();
            _selectedProject.Status = status;
            _selectedProject.Priority = priority;
            _selectedProject.ProjectColorLabel = color;
            _selectedProject.Description = TxtDescription.Text.Trim();
            _selectedProject.Notes = TxtNotes.Text.Trim();
            _selectedProject.Files = TxtFiles.Text.Trim();

            try
            {
                if (isNew)
                {
                    _projectRepo.Add(_selectedProject);

                    decimal installmentAmt = contractAmt / 3;
                    var inst = new Installment
                    {
                        ProjectId = _selectedProject.Id,
                        Installment1Amount = Math.Round(installmentAmt),
                        Installment1DueDate = JalaliDateHelper.AddDays(_selectedProject.StartDate, 10),
                        Installment1Paid = false,
                        Installment2Amount = Math.Round(installmentAmt),
                        Installment2DueDate = JalaliDateHelper.AddDays(_selectedProject.StartDate, 20),
                        Installment2Paid = false,
                        Installment3Amount = Math.Round(contractAmt - (Math.Round(installmentAmt) * 2)),
                        Installment3DueDate = _selectedProject.DeliveryDate,
                        Installment3Paid = false
                    };
                    _installmentRepo.Add(inst);

                    _activityRepo.Add(new Activity
                    {
                        Description = $"پروژه جدید ثبت و زمانبندی اقساط ایجاد شد: {_selectedProject.ProjectTitle}",
                        ShamsiDate = JalaliDateHelper.GetCurrentShamsiDate(),
                        Type = "پروژه"
                    });
                }
                else
                {
                    _projectRepo.Update(_selectedProject);

                    var inst = _installmentRepo.GetByProjectId(_selectedProject.Id);
                    if (inst != null)
                    {
                        decimal newInstAmt = contractAmt / 3;
                        inst.Installment1Amount = Math.Round(newInstAmt);
                        inst.Installment2Amount = Math.Round(newInstAmt);
                        inst.Installment3Amount = Math.Round(contractAmt - (Math.Round(newInstAmt) * 2));
                        _installmentRepo.Update(inst);
                    }

                    _activityRepo.Add(new Activity
                    {
                        Description = $"پروژه بروزرسانی شد: {_selectedProject.ProjectTitle}",
                        ShamsiDate = JalaliDateHelper.GetCurrentShamsiDate(),
                        Type = "پروژه"
                    });
                }

                var clients = _clientRepo.GetAll();
                if (!clients.Any(c => c.ClientName == _selectedProject.ClientName))
                {
                    _clientRepo.Add(new Client
                    {
                        ClientName = _selectedProject.ClientName,
                        Phone = _selectedProject.PhoneNumber,
                        Company = _selectedProject.CompanyName,
                        TotalPaid = 0,
                        RemainingBalance = _selectedProject.ContractAmount
                    });
                }

                MessageBox.Show("پروژه با موفقیت ذخیره گردید.", "عملیات موفق", MessageBoxButton.OK, MessageBoxImage.Information);
                LoadProjects();
                ResetForm();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در ذخیره‌سازی پروژه: {ex.Message}");
            }
        }

        private void BtnDelete_Click(object sender, RoutedEventArgs e)
        {
            if (_selectedProject == null) return;

            var res = MessageBox.Show($"آیا از حذف پروژه '{_selectedProject.ProjectTitle}' اطمینان دارید؟ تمامی اقساط مربوطه نیز حذف خواهند شد.", "تایید حذف", MessageBoxButton.YesNo, MessageBoxImage.Warning);
            if (res == MessageBoxResult.Yes)
            {
                try
                {
                    _projectRepo.Delete(_selectedProject.Id);
                    _activityRepo.Add(new Activity
                    {
                        Description = $"پروژه حذف شد: {_selectedProject.ProjectTitle}",
                        ShamsiDate = JalaliDateHelper.GetCurrentShamsiDate(),
                        Type = "پروژه"
                    });

                    MessageBox.Show("پروژه با موفقیت حذف گردید.", "عملیات موفق");
                    LoadProjects();
                    ResetForm();
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"خطا در حذف پروژه: {ex.Message}");
                }
            }
        }

        private void FileBorder_DragEnter(object sender, DragEventArgs e)
        {
            if (e.Data.GetDataPresent(DataFormats.FileDrop))
                e.Effects = DragDropEffects.Copy;
            else
                e.Effects = DragDropEffects.None;
        }

        private void FileBorder_Drop(object sender, DragEventArgs e)
        {
            if (e.Data.GetDataPresent(DataFormats.FileDrop))
            {
                string[] files = (string[])e.Data.GetData(DataFormats.FileDrop);
                if (files != null && files.Length > 0)
                {
                    TxtFiles.Text = files[0];
                    TxtFileLabel.Text = Path.GetFileName(files[0]);
                }
            }
        }

        public void PerformSearch(string query)
        {
            query = query.ToLower();
            var filtered = _allProjects.Where(p =>
                p.ProjectTitle.ToLower().Contains(query) ||
                p.ClientName.ToLower().Contains(query) ||
                p.Status.ToLower().Contains(query) ||
                p.ProjectCategory.ToLower().Contains(query)
            ).ToList();

            LstProjects.ItemsSource = filtered;
        }

        private void SelectComboByContent(ComboBox combo, string content)
        {
            foreach (ComboBoxItem item in combo.Items)
            {
                if (item.Content?.ToString() == content)
                {
                    combo.SelectedItem = item;
                    break;
                }
            }
        }

        private void SelectComboByTag(ComboBox combo, string tag)
        {
            foreach (ComboBoxItem item in combo.Items)
            {
                if (item.Tag?.ToString() == tag)
                {
                    combo.SelectedItem = item;
                    break;
                }
            }
        }
    }
}