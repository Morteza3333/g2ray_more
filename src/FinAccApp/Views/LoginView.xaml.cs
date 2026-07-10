using System.Windows;
using System.Windows.Controls;
using FinAccApp.ViewModels;

namespace FinAccApp.Views
{
    public partial class LoginView : UserControl
    {
        public LoginView()
        {
            InitializeComponent();
            DataContextChanged += LoginView_DataContextChanged;
        }

        private void LoginView_DataContextChanged(object sender, DependencyPropertyChangedEventArgs e)
        {
            if (DataContext is LoginViewModel vm)
            {
                TxtPassword.PasswordChanged += (s, ev) =>
                {
                    vm.Password = TxtPassword.Password;
                };
            }
        }
    }
}