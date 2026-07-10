using System.Collections.Generic;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public interface IRepository<T>
    {
        T GetById(int id);
        IEnumerable<T> GetAll();
        void Add(T entity);
        void Update(T entity);
        void Delete(int id);
    }

    public interface IProjectRepository : IRepository<Project> { }
    public interface IInstallmentRepository : IRepository<Installment>
    {
        Installment GetByProjectId(int projectId);
    }
    public interface IIncomeRepository : IRepository<Income> { }
    public interface IExpenseRepository : IRepository<Expense> { }
    public interface IClientRepository : IRepository<Client> { }
    public interface IInvoiceRepository : IRepository<Invoice> { }
    public interface IActivityRepository : IRepository<Activity> { }
    public interface INotificationRepository : IRepository<Notification> { }
}