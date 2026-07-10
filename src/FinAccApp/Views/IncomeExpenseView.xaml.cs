using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Media;
using FinAccApp.Data.Repositories;
using FinAccApp.Helpers;
using FinAccApp.Models;
using Microsoft.Win32;

namespace FinAccApp.Views
{
    public partial class IncomeExpenseView : UserControl
    {
        private readonly object _repo;
        private readonly ProjectRepository _projectRepo;
        private readonly bool _isExpense;

        private List<TransWrapper> _allTransactions = new List<TransWrapper>();
        private TransWrapper _selectedTrans = null!;

        public IncomeExpenseView(object repo, ProjectRepository projectRepo, bool isExpense)
        {
            InitializeComponent();
            _repo = repo;
            _projectRepo = projectRepo;
            _isExpense = isExpense;

            Loaded += IncomeExpenseView_Loaded;
        }

        private void IncomeExpenseView_Loaded(object sender, RoutedEventArgs e)
        {
            SetupCategories();
            SetupProjectCombo();
            LoadTransactions();
            ResetForm();
        }

        private void SetupCategories()
        {
            CboCategory.Items.Clear();
            if (_isExpense)
            {
                TxtFormTitle.Text = "ثبت هزینه جدید استودیو";
                TxtListTitle.Text = "ریز هزینه‌های ثبت شده";

                string[] categories = new[]
                {
                    "اجاره دفتر کار", "اینترنت دفتر", "هاستینگ", "دامنه وب‌سایت", "تبلیغات و بازاریابی",
                    "حقوق و دستمزد پرسنل", "بیمه", "مالیات", "تجهیزات و سخت‌افزار", "حمل و نقل و ایاب و ذهاب",
                    "اشتراک نرم‌افزارها", "سایر هزینه‌های عمومی"
                };
                foreach (var c in categories) CboCategory.Items.Add(new ComboBoxItem { Content = c });
            }
            else
            {
                TxtFormTitle.Text = "ثبت درآمد جدید پروژه";
                TxtListTitle.Text = "ریز درآمدهای ثبت شده";
                GridProjectRow.Visibility = Visibility.Visible;

                string[] categories = new[] { "درآمد پروژه وب‌سایت", "درآمد دیجیتال مارکتینگ", "درآمد مشاوره", "سایر درآمدها" };
                foreach (var c in categories) CboCategory.Items.Add(new ComboBoxItem { Content = c });
            }
        }

        private void SetupProjectCombo()
        {
            if (!_isExpense && _projectRepo != null)
            {
                CboProject.Items.Clear();
                try
                {
                    var projects = _projectRepo.GetAll();
                    CboProject.Items.Add(new ComboBoxItem { Content = "بدون پروژه", Tag = "0" });
                    foreach (var p in projects)
                    {
                        CboProject.Items.Add(new ComboBoxItem { Content = p.ProjectTitle, Tag = p.Id.ToString() });
                    }
                    CboProject.SelectedIndex = 0;
                }
                catch { }
            }
        }

        private void LoadTransactions()
        {
            try
            {
                _allTransactions.Clear();
                if (_isExpense)
                {
                    var repo = (IExpenseRepository)_repo;
                    var data = repo.GetAll();
                    _allTransactions = data.Select(x => new TransWrapper(x)).ToList();
                }
                else
                {
                    var repo = (IIncomeRepository)_repo;
                    var data = repo.GetAll();
                    _allTransactions = data.Select(x => new TransWrapper(x)).ToList();
                }

                LstTransactions.ItemsSource = null;
                LstTransactions.ItemsSource = _allTransactions;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در دریافت لیست تراکنش‌ها: {ex.Message}");
            }
        }

        private void ResetForm()
        {
            _selectedTrans = null!;
            TxtDate.Text = JalaliDateHelper.GetCurrentShamsiDate();
            TxtAmount.Text = "0";
            CboCategory.SelectedIndex = 0;
            TxtClient.Text = string.Empty;
            if (CboProject.Items.Count > 0) CboProject.SelectedIndex = 0;
            ChkPersonal.IsChecked = false;
            TxtDescription.Text = string.Empty;
            TxtAttachment.Text = string.Empty;
        }

        private void LstTransactions_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (LstTransactions.SelectedItem is TransWrapper wrapper)
            {
                _selectedTrans = wrapper;
                TxtDate.Text = wrapper.TransDate;
                TxtAmount.Text = wrapper.Amount.ToString("0");
                TxtDescription.Text = wrapper.Description;
                TxtAttachment.Text = wrapper.Attachment;
                ChkPersonal.IsChecked = wrapper.IsPersonal;

                SelectComboItem(CboCategory, wrapper.Category);

                if (!_isExpense)
                {
                    TxtClient.Text = wrapper.Client;
                    SelectComboItemByTag(CboProject, wrapper.ProjectId);
                }
            }
        }

        private void BtnNew_Click(object sender, RoutedEventArgs e)
        {
            ResetForm();
            LstTransactions.SelectedItem = null;
        }

        private void BtnSave_Click(object sender, RoutedEventArgs e)
        {
            decimal.TryParse(TxtAmount.Text, out decimal amt);
            if (amt <= 0)
            {
                MessageBox.Show("لطفاً مبلغ تراکنش را وارد نمایید.", "خطا", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            string cat = (CboCategory.SelectedItem as ComboBoxItem)?.Content?.ToString() ?? "سایر";
            string desc = TxtDescription.Text.Trim();
            string date = TxtDate.Text.Trim();
            string attach = TxtAttachment.Text.Trim();
            bool isPersonal = ChkPersonal.IsChecked == true;

            try
            {
                if (_isExpense)
                {
                    var repo = (IExpenseRepository)_repo;
                    var exp = _selectedTrans != null ? (Expense)_selectedTrans.Source : new Expense();

                    exp.ExpenseDate = date;
                    exp.Amount = amt;
                    exp.Category = cat;
                    exp.Description = desc;
                    exp.ReceiptAttachment = attach;
                    exp.IsPersonal = isPersonal;

                    if (_selectedTrans == null) repo.Add(exp);
                    else repo.Update(exp);
                }
                else
                {
                    var repo = (IIncomeRepository)_repo;
                    var inc = _selectedTrans != null ? (Income)_selectedTrans.Source : new Income();

                    inc.IncomeDate = date;
                    inc.Amount = amt;
                    inc.Category = cat;
                    inc.Description = desc;
                    inc.Attachment = attach;
                    inc.IsPersonal = isPersonal;
                    inc.Client = TxtClient.Text.Trim();

                    var projTag = (CboProject.SelectedItem as ComboBoxItem)?.Tag?.ToString() ?? "0";
                    inc.Project = (CboProject.SelectedItem as ComboBoxItem)?.Content?.ToString() ?? string.Empty;

                    if (_selectedTrans == null) repo.Add(inc);
                    else repo.Update(inc);
                }

                MessageBox.Show("تراکنش مالی با موفقیت ذخیره شد.", "موفقیت", MessageBoxButton.OK, MessageBoxImage.Information);
                LoadTransactions();
                ResetForm();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در ذخیره تراکنش: {ex.Message}");
            }
        }

        private void BtnDelete_Click(object sender, RoutedEventArgs e)
        {
            if (_selectedTrans == null) return;

            var res = MessageBox.Show("آیا مطمئن هستید که می‌خواهید این تراکنش را حذف کنید؟", "تایید حذف", MessageBoxButton.YesNo, MessageBoxImage.Warning);
            if (res == MessageBoxResult.Yes)
            {
                try
                {
                    if (_isExpense)
                    {
                        var repo = (IExpenseRepository)_repo;
                        repo.Delete(((Expense)_selectedTrans.Source).Id);
                    }
                    else
                    {
                        var repo = (IIncomeRepository)_repo;
                        repo.Delete(((Income)_selectedTrans.Source).Id);
                    }

                    MessageBox.Show("تراکنش حذف شد.", "موفقیت");
                    LoadTransactions();
                    ResetForm();
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"خطا در حذف تراکنش: {ex.Message}");
                }
            }
        }

        private void BtnAttach_Click(object sender, RoutedEventArgs e)
        {
            var openFileDialog = new OpenFileDialog();
            if (openFileDialog.ShowDialog() == true)
            {
                TxtAttachment.Text = openFileDialog.FileName;
            }
        }

        private void SelectComboItem(ComboBox combo, string content)
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

        private void SelectComboItemByTag(ComboBox combo, string tag)
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

    public class TransWrapper
    {
        public object Source { get; }

        public TransWrapper(Expense exp) { Source = exp; }
        public TransWrapper(Income inc) { Source = inc; }

        public string TransDate => Source is Expense e ? e.ExpenseDate : ((Income)Source).IncomeDate;
        public string Category => Source is Expense e ? e.Category : ((Income)Source).Category;
        public decimal Amount => Source is Expense e ? e.Amount : ((Income)Source).Amount;
        public string Description => Source is Expense e ? e.Description : ((Income)Source).Description;
        public string Attachment => Source is Expense e ? e.ReceiptAttachment : ((Income)Source).Attachment;
        public bool IsPersonal => Source is Expense e ? e.IsPersonal : ((Income)Source).IsPersonal;
        public string Client => Source is Income i ? i.Client : string.Empty;
        public string ProjectName => Source is Income i ? i.Project : string.Empty;
        public string ProjectId => "0";

        public Brush AmountColorBrush => Source is Expense
            ? new SolidColorBrush((Color)ColorConverter.ConvertFromString("#E74C3C"))
            : new SolidColorBrush((Color)ColorConverter.ConvertFromString("#2ECC71"));
    }
}