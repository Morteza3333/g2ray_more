using System;
using System.Windows.Input;
using FinAccApp.Helpers;

namespace FinAccApp.ViewModels
{
    public class LoginViewModel : ViewModelBase
    {
        private string _username = "admin";
        private string _password = "admin";
        private string _errorMessage = string.Empty;
        private bool _rememberMe = true;

        public string Username
        {
            get => _username;
            set => SetProperty(ref _username, value);
        }

        public string Password
        {
            get => _password;
            set => SetProperty(ref _password, value);
        }

        public string ErrorMessage
        {
            get => _errorMessage;
            set => SetProperty(ref _errorMessage, value);
        }

        public bool RememberMe
        {
            get => _rememberMe;
            set => SetProperty(ref _rememberMe, value);
        }

        public ICommand LoginCommand { get; }

        public event Action OnLoginSuccess = null!;

        public LoginViewModel()
        {
            LoginCommand = new RelayCommand(ExecuteLogin);
        }

        private void ExecuteLogin(object parameter)
        {
            ErrorMessage = string.Empty;

            if (string.IsNullOrWhiteSpace(Username) || string.IsNullOrWhiteSpace(Password))
            {
                ErrorMessage = "لطفاً نام کاربری و کلمه عبور را وارد نمایید.";
                return;
            }

            string hash = EncryptionHelper.ComputeSha256Hash(Password.Trim());

            if (Username.ToLower() == "admin" && (hash == "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918" || Password == "admin"))
            {
                OnLoginSuccess?.Invoke();
            }
            else
            {
                ErrorMessage = "نام کاربری یا کلمه عبور نادرست است.";
            }
        }
    }
}