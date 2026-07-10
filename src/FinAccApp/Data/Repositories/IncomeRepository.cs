using System;
using System.Collections.Generic;
using Microsoft.Data.Sqlite;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public class IncomeRepository : IIncomeRepository
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public Income GetById(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Incomes WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    using (var reader = cmd.ExecuteReader())
                    {
                        if (reader.Read()) return MapReader(reader);
                    }
                }
            }
            return null!;
        }

        public IEnumerable<Income> GetAll()
        {
            var list = new List<Income>();
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Incomes ORDER BY Id DESC;";
                using (var cmd = new SqliteCommand(query, conn))
                using (var reader = cmd.ExecuteReader())
                {
                    while (reader.Read()) list.Add(MapReader(reader));
                }
            }
            return list;
        }

        public void Add(Income entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    INSERT INTO Incomes (IncomeDate, Category, Client, Project, Amount, PaymentMethod, Description, Attachment, IsPersonal)
                    VALUES (@IncomeDate, @Category, @Client, @Project, @Amount, @PaymentMethod, @Description, @Attachment, @IsPersonal);
                    SELECT last_insert_rowid();";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    AddParameters(cmd, entity);
                    entity.Id = Convert.ToInt32(cmd.ExecuteScalar());
                }
            }
        }

        public void Update(Income entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    UPDATE Incomes SET
                        IncomeDate = @IncomeDate, Category = @Category, Client = @Client, Project = @Project,
                        Amount = @Amount, PaymentMethod = @PaymentMethod, Description = @Description,
                        Attachment = @Attachment, IsPersonal = @IsPersonal
                    WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", entity.Id);
                    AddParameters(cmd, entity);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        public void Delete(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "DELETE FROM Incomes WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        private Income MapReader(SqliteDataReader reader)
        {
            return new Income
            {
                Id = Convert.ToInt32(reader["Id"]),
                IncomeDate = reader["IncomeDate"]?.ToString() ?? "",
                Category = reader["Category"]?.ToString() ?? "درآمد پروژه",
                Client = reader["Client"]?.ToString() ?? "",
                Project = reader["Project"]?.ToString() ?? "",
                Amount = Convert.ToDecimal(reader["Amount"]),
                PaymentMethod = reader["PaymentMethod"]?.ToString() ?? "نقدی",
                Description = reader["Description"]?.ToString() ?? "",
                Attachment = reader["Attachment"]?.ToString() ?? "",
                IsPersonal = Convert.ToInt32(reader["IsPersonal"]) == 1
            };
        }

        private void AddParameters(SqliteCommand cmd, Income entity)
        {
            cmd.Parameters.AddWithValue("@IncomeDate", entity.IncomeDate);
            cmd.Parameters.AddWithValue("@Category", entity.Category);
            cmd.Parameters.AddWithValue("@Client", entity.Client);
            cmd.Parameters.AddWithValue("@Project", entity.Project);
            cmd.Parameters.AddWithValue("@Amount", entity.Amount);
            cmd.Parameters.AddWithValue("@PaymentMethod", entity.PaymentMethod);
            cmd.Parameters.AddWithValue("@Description", entity.Description);
            cmd.Parameters.AddWithValue("@Attachment", entity.Attachment);
            cmd.Parameters.AddWithValue("@IsPersonal", entity.IsPersonal ? 1 : 0);
        }
    }
}