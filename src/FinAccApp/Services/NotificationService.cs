using System;
using System.Collections.Generic;
using System.Linq;
using FinAccApp.Data.Repositories;
using FinAccApp.Models;
using FinAccApp.Helpers;

namespace FinAccApp.Services
{
    public class NotificationService
    {
        private readonly INotificationRepository _notificationRepo;
        private readonly IProjectRepository _projectRepo;
        private readonly IInstallmentRepository _installmentRepo;

        public NotificationService(
            INotificationRepository notificationRepo,
            IProjectRepository projectRepo,
            IInstallmentRepository installmentRepo)
        {
            _notificationRepo = notificationRepo;
            _projectRepo = projectRepo;
            _installmentRepo = installmentRepo;
        }

        public void CheckAndGenerateNotifications(decimal currentCashBalance)
        {
            string today = JalaliDateHelper.GetCurrentShamsiDate();

            if (currentCashBalance < 5000000)
            {
                CreateUniqueNotification(
                    "هشدار نقدینگی پایین",
                    $"نقدینگی صندوق استودیو در وضعیت بحرانی است: {currentCashBalance:N0} تومان",
                    "مالی"
                );
            }

            var projects = _projectRepo.GetAll().Where(p => p.Status != "لغو شده" && p.Status != "پرداخت کامل");
            foreach (var p in projects)
            {
                var inst = _installmentRepo.GetByProjectId(p.Id);
                if (inst != null)
                {
                    CheckInstallmentDue(inst.Installment1DueDate, inst.Installment1Amount, inst.Installment1Paid, p.ProjectTitle, 1);
                    CheckInstallmentDue(inst.Installment2DueDate, inst.Installment2Amount, inst.Installment2Paid, p.ProjectTitle, 2);
                    CheckInstallmentDue(inst.Installment3DueDate, inst.Installment3Amount, inst.Installment3Paid, p.ProjectTitle, 3);
                }

                if (!string.IsNullOrEmpty(p.DeliveryDate) && p.Status == "در حال انجام")
                {
                    int daysDiff = JalaliDateHelper.GetDaysDifference(today, p.DeliveryDate);
                    if (daysDiff >= 0 && daysDiff <= 5)
                    {
                        CreateUniqueNotification(
                            "سررسید مهلت تحویل پروژه",
                            $"پروژه '{p.ProjectTitle}' باید تا {daysDiff} روز آینده تحویل داده شود.",
                            "سررسید"
                        );
                    }
                    else if (daysDiff < 0)
                    {
                        CreateUniqueNotification(
                            "تاخیر در تحویل پروژه",
                            $"مهلت تحویل پروژه '{p.ProjectTitle}' به مدت {Math.Abs(daysDiff)} روز منقضی شده است.",
                            "سررسید"
                        );
                    }
                }
            }
        }

        private void CheckInstallmentDue(string dueDate, decimal amount, bool isPaid, string projectTitle, int instIndex)
        {
            if (isPaid || string.IsNullOrEmpty(dueDate)) return;

            string today = JalaliDateHelper.GetCurrentShamsiDate();
            int daysDiff = JalaliDateHelper.GetDaysDifference(today, dueDate);

            if (daysDiff >= 0 && daysDiff <= 7)
            {
                CreateUniqueNotification(
                    "سررسید قسط پروژه",
                    $"قسط {instIndex} پروژه '{projectTitle}' به مبلغ {amount:N0} تومان در تاریخ {dueDate} سررسید می‌شود.",
                    "قسط"
                );
            }
            else if (daysDiff < 0)
            {
                CreateUniqueNotification(
                    "قسط معوقه",
                    $"پرداخت قسط {instIndex} پروژه '{projectTitle}' به مبلغ {amount:N0} تومان به مدت {Math.Abs(daysDiff)} روز به تعویق افتاده است.",
                    "قسط"
                );
            }
        }

        private void CreateUniqueNotification(string title, string description, string type)
        {
            var all = _notificationRepo.GetAll();
            bool exists = all.Any(n => n.Title == title && n.Description == description && !n.IsRead);
            if (!exists)
            {
                _notificationRepo.Add(new Notification
                {
                    Title = title,
                    Description = description,
                    ShamsiDate = JalaliDateHelper.GetCurrentShamsiDate(),
                    IsRead = false,
                    Type = type
                });
            }
        }
    }
}