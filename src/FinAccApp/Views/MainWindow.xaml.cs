using FinAccApp.Helpers;
using System;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data;
using FinAccApp.Data.Repositories;
using FinAccApp.Models;
using FinAccApp.Services;
using FinAccApp.ViewModels;

namespace FinAccApp.Views
{
    public partial class MainWindow : Window
    {
        private ProjectRepository _projectRepo = null!;
        private InstallmentRepository _installmentRepo = null!;
        private IncomeRepository _incomeRepo = null!;
        private ExpenseRepository _expenseRepo = null!;
        private ClientRepository _clientRepo = null!;
        private InvoiceRepository _invoiceRepo = null!;
        private ActivityRepository _activityRepo = null!;
        private NotificationRepository _notificationRepo = null!;
        private NotificationService _notificationService = null!;

        private string _companyName = "استودیو طراحی وب شمس";
        private bool _isDarkMode = false;

        public string CompanyName
        {
            get => _companyName;
            set => _companyName = value;
        }

        public MainWindow()
        {
            InitializeComponent();
            DataContext = this;

            try
            {
                DatabaseInitializer.Initialize();

                _projectRepo = new ProjectRepository();
                _installmentRepo = new InstallmentRepository();
                _incomeRepo = new IncomeRepository();
                _expenseRepo = new ExpenseRepository();
                _clientRepo = new ClientRepository();
                _invoiceRepo = new InvoiceRepository();
                _activityRepo = new ActivityRepository();
                _notificationRepo = new NotificationRepository();

                _notificationService = new NotificationService(_notificationRepo, _projectRepo, _installmentRepo);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در راه‌اندازی پایگاه‌داده: {ex.Message}", "خطای سیستم", MessageBoxButton.OK, MessageBoxImage.Error);
            }

            Loaded += MainWindow_Loaded;
        }

        private void MainWindow_Loaded(object sender, RoutedEventArgs e)
        {
            ShowLoginScreen();
            UpdateNotificationsCount();
        }

        private void ShowLoginScreen()
        {
            MainWorkspace.Visibility = Visibility.Collapsed;
            LoginContent.Visibility = Visibility.Visible;

            var loginVM = new LoginViewModel();
            var loginView = new LoginView { DataContext = loginVM };

            loginVM.OnLoginSuccess += () =>
            {
                LoginContent.Visibility = Visibility.Collapsed;
                MainWorkspace.Visibility = Visibility.Visible;
                NavigateTo("Dashboard");
                TriggerNotificationCheck();
            };

            LoginContent.Content = loginView;
        }

        private void TriggerNotificationCheck()
        {
            try
            {
                decimal totalCash = GetStudioCurrentCash();
                _notificationService.CheckAndGenerateNotifications(totalCash);
                UpdateNotificationsCount();
            }
            catch { }
        }

        private decimal GetStudioCurrentCash()
        {
            decimal totalIncome = _incomeRepo.GetAll().Where(i => !i.IsPersonal).Sum(i => i.Amount);
            decimal totalExpense = _expenseRepo.GetAll().Where(e => !e.IsPersonal).Sum(e => e.Amount);
            return totalIncome - totalExpense;
        }

        public void UpdateNotificationsCount()
        {
            try
            {
                int unreadCount = _notificationRepo.GetAll().Count(n => !n.IsRead);
                BtnNotifications.Content = $"🔔 اعلان‌ها ({unreadCount})";
            }
            catch { }
        }

        public void NavigateTo(string tag)
        {
            if (string.IsNullOrEmpty(tag)) return;

            foreach (var child in NavStack.Children)
            {
                if (child is Button btn)
                {
                    if (btn.Tag?.ToString() == tag)
                        btn.Background = (System.Windows.Media.Brush)FindResource("PrimaryBrush");
                    else
                        btn.Background = System.Windows.Media.Brushes.Transparent;
                }
            }

            switch (tag)
            {
                case "Dashboard":
                    TxtPageHeader.Text = "داشبورد خلاصه حساب‌ها";
                    WorkspaceViewport.Content = new DashboardView(_projectRepo, _installmentRepo, _incomeRepo, _expenseRepo, _activityRepo);
                    break;
                case "Projects":
                    TxtPageHeader.Text = "مدیریت پروژه‌ها";
                    WorkspaceViewport.Content = new ProjectsView(_projectRepo, _installmentRepo, _clientRepo, _activityRepo);
                    break;
                case "Installments":
                    TxtPageHeader.Text = "مدیریت اقساط و زمان‌بندی";
                    WorkspaceViewport.Content = new InstallmentsView(_installmentRepo, _projectRepo, _activityRepo);
                    break;
                case "Income":
                    TxtPageHeader.Text = "ثبت درآمدهای استودیو";
                    WorkspaceViewport.Content = new IncomeExpenseView(_incomeRepo, _projectRepo, isExpense: false);
                    break;
                case "Expenses":
                    TxtPageHeader.Text = "هزینه‌های استودیو";
                    WorkspaceViewport.Content = new IncomeExpenseView(_expenseRepo, null!, isExpense: true);
                    break;
                case "Personal":
                    TxtPageHeader.Text = "امور مالی شخصی";
                    WorkspaceViewport.Content = new PersonalFinanceView(_incomeRepo, _expenseRepo);
                    break;
                case "Clients":
                    TxtPageHeader.Text = "مدیریت مشتریان";
                    WorkspaceViewport.Content = new ClientsView(_clientRepo);
                    break;
                case "Invoices":
                    TxtPageHeader.Text = "صدور و مدیریت فاکتور";
                    WorkspaceViewport.Content = new InvoicesView(_invoiceRepo);
                    break;
                case "Reports":
                    TxtPageHeader.Text = "گزارشات مالی و حسابداری";
                    WorkspaceViewport.Content = new ReportsView(_projectRepo, _incomeRepo, _expenseRepo, _clientRepo, _installmentRepo);
                    break;
                case "Settings":
                    TxtPageHeader.Text = "تنظیمات نرم‌افزار";
                    WorkspaceViewport.Content = new SettingsView();
                    break;
            }
        }

        private void Nav_Click(object sender, RoutedEventArgs e)
        {
            if (sender is Button btn && btn.Tag != null)
            {
                NavigateTo(btn.Tag.ToString()!);
            }
        }

        private void ToggleTheme_Click(object sender, RoutedEventArgs e)
        {
            _isDarkMode = !_isDarkMode;

            Application.Current.Resources.MergedDictionaries.Clear();

            var themeDict = new ResourceDictionary();
            themeDict.Source = new Uri(_isDarkMode ? "Styles/DarkTheme.xaml" : "Styles/LightTheme.xaml", UriKind.Relative);
            Application.Current.Resources.MergedDictionaries.Add(themeDict);

            var styleDict = new ResourceDictionary();
            styleDict.Source = new Uri("Styles/GlassmorphismStyles.xaml", UriKind.Relative);
            Application.Current.Resources.MergedDictionaries.Add(styleDict);

            if (sender is Button btn)
            {
                btn.Content = _isDarkMode ? "حالت روشن" : "حالت تاریک";
            }
        }

        private void Logout_Click(object sender, RoutedEventArgs e)
        {
            var res = MessageBox.Show("آیا مطمئن هستید که می‌خواهید از سامانه خارج شوید؟", "خروج", MessageBoxButton.YesNo, MessageBoxImage.Question);
            if (res == MessageBoxResult.Yes)
            {
                ShowLoginScreen();
            }
        }

        private void GlobalSearch_TextChanged(object sender, TextChangedEventArgs e)
        {
            string query = TxtGlobalSearch.Text.Trim();
            if (WorkspaceViewport.Content is ISearchable searchable)
            {
                searchable.PerformSearch(query);
            }
        }

        private void BtnNotifications_Click(object sender, RoutedEventArgs e)
        {
            var notifyWindow = new Window
            {
                Title = "اعلان‌ها و یادآوری‌ها",
                Width = 450,
                Height = 500,
                WindowStartupLocation = WindowStartupLocation.CenterOwner,
                Owner = this,
                Background = (System.Windows.Media.Brush)FindResource("WindowBgBrush"),
                FlowDirection = FlowDirection.RightToLeft
            };

            var items = _notificationRepo.GetAll();
            var listbox = new ListBox
            {
                ItemsSource = items,
                DisplayMemberPath = "Description",
                Margin = new Thickness(15),
                Background = System.Windows.Media.Brushes.Transparent,
                BorderThickness = new Thickness(0)
            };

            var grid = new Grid();
            grid.RowDefinitions.Add(new RowDefinition { Height = new GridLength(1, GridUnitType.Star) });
            grid.RowDefinitions.Add(new RowDefinition { Height = GridLength.Auto });

            Grid.SetRow(listbox, 0);
            grid.Children.Add(listbox);

            var clearBtn = new Button { Content = "خوانده شدن همه اعلان‌ها", Margin = new Thickness(15), Height = 40 };
            clearBtn.Click += (s, ev) =>
            {
                foreach (var n in items)
                {
                    n.IsRead = true;
                    _notificationRepo.Update(n);
                }
                UpdateNotificationsCount();
                notifyWindow.Close();
            };

            Grid.SetRow(clearBtn, 1);
            grid.Children.Add(clearBtn);

            notifyWindow.Content = grid;
            notifyWindow.ShowDialog();
        }

        private void QuickAddProject_Click(object sender, RoutedEventArgs e)
        {
            var quickAddWin = new Window
            {
                Title = "ثبت سریع پروژه جدید",
                Width = 400,
                Height = 450,
                WindowStartupLocation = WindowStartupLocation.CenterOwner,
                Owner = this,
                Background = (System.Windows.Media.Brush)FindResource("WindowBgBrush"),
                FlowDirection = FlowDirection.RightToLeft
            };

            var stack = new StackPanel { Margin = new Thickness(20) };

            stack.Children.Add(new TextBlock { Text = "نام مشتری", Margin = new Thickness(0,0,0,5) });
            var clientTxt = new TextBox { Margin = new Thickness(0,0,0,12) };
            stack.Children.Add(clientTxt);

            stack.Children.Add(new TextBlock { Text = "عنوان پروژه", Margin = new Thickness(0,0,0,5) });
            var titleTxt = new TextBox { Margin = new Thickness(0,0,0,12) };
            stack.Children.Add(titleTxt);

            stack.Children.Add(new TextBlock { Text = "مبلغ قرارداد (تومان)", Margin = new Thickness(0,0,0,5) });
            var contractTxt = new TextBox { Margin = new Thickness(0,0,0,12) };
            stack.Children.Add(contractTxt);

            var saveBtn = new Button { Content = "ذخیره پروژه جدید", Height = 40, Margin = new Thickness(0,10,0,0) };
            saveBtn.Click += (s, ev) =>
            {
                if (string.IsNullOrEmpty(clientTxt.Text) || string.IsNullOrEmpty(titleTxt.Text))
                {
                    MessageBox.Show("لطفا تمامی فیلدها را پر کنید.", "خطا", MessageBoxButton.OK, MessageBoxImage.Warning);
                    return;
                }

                decimal.TryParse(contractTxt.Text, out decimal contractAmt);

                var newProject = new Project
                {
                    ClientName = clientTxt.Text,
                    ProjectTitle = titleTxt.Text,
                    ContractAmount = contractAmt,
                    StartDate = JalaliDateHelper.GetCurrentShamsiDate(),
                    DeliveryDate = JalaliDateHelper.AddDays(JalaliDateHelper.GetCurrentShamsiDate(), 30),
                    Status = "در حال انجام",
                    Priority = "متوسط"
                };

                _projectRepo.Add(newProject);

                decimal installmentAmt = contractAmt / 3;
                var newInst = new Installment
                {
                    ProjectId = newProject.Id,
                    Installment1Amount = installmentAmt,
                    Installment1DueDate = JalaliDateHelper.AddDays(newProject.StartDate, 10),
                    Installment1Paid = false,
                    Installment2Amount = installmentAmt,
                    Installment2DueDate = JalaliDateHelper.AddDays(newProject.StartDate, 20),
                    Installment2Paid = false,
                    Installment3Amount = installmentAmt,
                    Installment3DueDate = newProject.DeliveryDate,
                    Installment3Paid = false
                };
                _installmentRepo.Add(newInst);

                _activityRepo.Add(new Activity
                {
                    Description = $"پروژه سریع ثبت شد: {newProject.ProjectTitle} برای {newProject.ClientName}",
                    ShamsiDate = JalaliDateHelper.GetCurrentShamsiDate(),
                    Type = "پروژه"
                });

                MessageBox.Show("پروژه با موفقیت و به همراه اقساط سه‌گانه خود ایجاد گردید.", "موفقیت", MessageBoxButton.OK, MessageBoxImage.Information);
                quickAddWin.Close();
                NavigateTo("Projects");
            };

            stack.Children.Add(saveBtn);
            quickAddWin.Content = stack;
            quickAddWin.ShowDialog();
        }
    }

    public interface ISearchable
    {
        void PerformSearch(string query);
    }
}