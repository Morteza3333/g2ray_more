using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using FinAccApp.Data.Repositories;
using FinAccApp.Models;

namespace FinAccApp.Views
{
    public partial class ClientsView : UserControl, ISearchable
    {
        private readonly ClientRepository _clientRepo;
        private List<Client> _allClients = new List<Client>();
        private Client _selectedClient = null!;

        public ClientsView(ClientRepository clientRepo)
        {
            InitializeComponent();
            _clientRepo = clientRepo;

            Loaded += ClientsView_Loaded;
        }

        private void ClientsView_Loaded(object sender, RoutedEventArgs e)
        {
            LoadClients();
            ResetForm();
        }

        private void LoadClients()
        {
            try
            {
                _allClients = _clientRepo.GetAll().ToList();
                LstClients.ItemsSource = _allClients;
            }
            catch { }
        }

        private void ResetForm()
        {
            _selectedClient = null!;
            TxtClientName.Text = string.Empty;
            TxtPhone.Text = string.Empty;
            TxtEmail.Text = string.Empty;
            TxtCompany.Text = string.Empty;
            TxtPaid.Text = "0";
            TxtRemaining.Text = "0";
            TxtAddress.Text = string.Empty;
            TxtNotes.Text = string.Empty;
        }

        private void LstClients_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (LstClients.SelectedItem is Client client)
            {
                _selectedClient = client;
                TxtClientName.Text = client.ClientName;
                TxtPhone.Text = client.Phone;
                TxtEmail.Text = client.Email;
                TxtCompany.Text = client.Company;
                TxtPaid.Text = client.TotalPaid.ToString("0");
                TxtRemaining.Text = client.RemainingBalance.ToString("0");
                TxtAddress.Text = client.Address;
                TxtNotes.Text = client.Notes;
            }
        }

        private void BtnNew_Click(object sender, RoutedEventArgs e)
        {
            ResetForm();
            LstClients.SelectedItem = null;
        }

        private void BtnSave_Click(object sender, RoutedEventArgs e)
        {
            if (string.IsNullOrWhiteSpace(TxtClientName.Text))
            {
                MessageBox.Show("لطفاً نام مشتری را وارد نمایید.", "خطا", MessageBoxButton.OK, MessageBoxImage.Warning);
                return;
            }

            decimal.TryParse(TxtPaid.Text, out decimal paid);
            decimal.TryParse(TxtRemaining.Text, out decimal remaining);

            bool isNew = (_selectedClient == null);
            if (isNew)
            {
                _selectedClient = new Client();
            }

            _selectedClient.ClientName = TxtClientName.Text.Trim();
            _selectedClient.Phone = TxtPhone.Text.Trim();
            _selectedClient.Email = TxtEmail.Text.Trim();
            _selectedClient.Company = TxtCompany.Text.Trim();
            _selectedClient.TotalPaid = paid;
            _selectedClient.RemainingBalance = remaining;
            _selectedClient.Address = TxtAddress.Text.Trim();
            _selectedClient.Notes = TxtNotes.Text.Trim();

            try
            {
                if (isNew) _clientRepo.Add(_selectedClient);
                else _clientRepo.Update(_selectedClient);

                MessageBox.Show("کارفرما با موفقیت ذخیره گردید.", "موفقیت", MessageBoxButton.OK, MessageBoxImage.Information);
                LoadClients();
                ResetForm();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"خطا در ذخیره کارفرما: {ex.Message}");
            }
        }

        private void BtnDelete_Click(object sender, RoutedEventArgs e)
        {
            if (_selectedClient == null) return;

            var res = MessageBox.Show($"آیا از حذف مشتری '{_selectedClient.ClientName}' اطمینان دارید؟", "حذف کارفرما", MessageBoxButton.YesNo, MessageBoxImage.Warning);
            if (res == MessageBoxResult.Yes)
            {
                try
                {
                    _clientRepo.Delete(_selectedClient.Id);
                    MessageBox.Show("مشتری حذف شد.", "عملیات موفقیت‌آمیز");
                    LoadClients();
                    ResetForm();
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"خطا در حذف کارفرما: {ex.Message}");
                }
            }
        }

        public void PerformSearch(string query)
        {
            query = query.ToLower();
            var filtered = _allClients.Where(c =>
                c.ClientName.ToLower().Contains(query) ||
                c.Company.ToLower().Contains(query) ||
                c.Phone.Contains(query)
            ).ToList();

            LstClients.ItemsSource = filtered;
        }
    }
}