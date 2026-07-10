using System;
using System.Collections.Generic;
using Microsoft.Data.Sqlite;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public class InstallmentRepository : IInstallmentRepository
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public Installment GetById(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Installments WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    using (var reader = cmd.ExecuteReader())
                    {
                        if (reader.Read()) return MapReaderToInstallment(reader);
                    }
                }
            }
            return null!;
        }

        public Installment GetByProjectId(int projectId)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Installments WHERE ProjectId = @ProjectId;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@ProjectId", projectId);
                    using (var reader = cmd.ExecuteReader())
                    {
                        if (reader.Read()) return MapReaderToInstallment(reader);
                    }
                }
            }
            return null!;
        }

        public IEnumerable<Installment> GetAll()
        {
            var list = new List<Installment>();
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Installments;";
                using (var cmd = new SqliteCommand(query, conn))
                using (var reader = cmd.ExecuteReader())
                {
                    while (reader.Read()) list.Add(MapReaderToInstallment(reader));
                }
            }
            return list;
        }

        public void Add(Installment entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    INSERT INTO Installments (ProjectId, Installment1Amount, Installment1DueDate, Installment1Paid, Installment2Amount, Installment2DueDate, Installment2Paid, Installment3Amount, Installment3DueDate, Installment3Paid)
                    VALUES (@ProjectId, @Installment1Amount, @Installment1DueDate, @Installment1Paid, @Installment2Amount, @Installment2DueDate, @Installment2Paid, @Installment3Amount, @Installment3DueDate, @Installment3Paid);
                    SELECT last_insert_rowid();";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    AddParameters(cmd, entity);
                    entity.Id = Convert.ToInt32(cmd.ExecuteScalar());
                }
            }
        }

        public void Update(Installment entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    UPDATE Installments SET
                        ProjectId = @ProjectId, Installment1Amount = @Installment1Amount, Installment1DueDate = @Installment1DueDate, Installment1Paid = @Installment1Paid,
                        Installment2Amount = @Installment2Amount, Installment2DueDate = @Installment2DueDate, Installment2Paid = @Installment2Paid,
                        Installment3Amount = @Installment3Amount, Installment3DueDate = @Installment3DueDate, Installment3Paid = @Installment3Paid
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
                string query = "DELETE FROM Installments WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        private Installment MapReaderToInstallment(SqliteDataReader reader)
        {
            return new Installment
            {
                Id = Convert.ToInt32(reader["Id"]),
                ProjectId = Convert.ToInt32(reader["ProjectId"]),
                Installment1Amount = Convert.ToDecimal(reader["Installment1Amount"]),
                Installment1DueDate = reader["Installment1DueDate"]?.ToString() ?? "",
                Installment1Paid = Convert.ToInt32(reader["Installment1Paid"]) == 1,
                Installment2Amount = Convert.ToDecimal(reader["Installment2Amount"]),
                Installment2DueDate = reader["Installment2DueDate"]?.ToString() ?? "",
                Installment2Paid = Convert.ToInt32(reader["Installment2Paid"]) == 1,
                Installment3Amount = Convert.ToDecimal(reader["Installment3Amount"]),
                Installment3DueDate = reader["Installment3DueDate"]?.ToString() ?? "",
                Installment3Paid = Convert.ToInt32(reader["Installment3Paid"]) == 1
            };
        }

        private void AddParameters(SqliteCommand cmd, Installment entity)
        {
            cmd.Parameters.AddWithValue("@ProjectId", entity.ProjectId);
            cmd.Parameters.AddWithValue("@Installment1Amount", entity.Installment1Amount);
            cmd.Parameters.AddWithValue("@Installment1DueDate", entity.Installment1DueDate);
            cmd.Parameters.AddWithValue("@Installment1Paid", entity.Installment1Paid ? 1 : 0);
            cmd.Parameters.AddWithValue("@Installment2Amount", entity.Installment2Amount);
            cmd.Parameters.AddWithValue("@Installment2DueDate", entity.Installment2DueDate);
            cmd.Parameters.AddWithValue("@Installment2Paid", entity.Installment2Paid ? 1 : 0);
            cmd.Parameters.AddWithValue("@Installment3Amount", entity.Installment3Amount);
            cmd.Parameters.AddWithValue("@Installment3DueDate", entity.Installment3DueDate);
            cmd.Parameters.AddWithValue("@Installment3Paid", entity.Installment3Paid ? 1 : 0);
        }
    }
}