using System;
using System.Collections.Generic;
using Microsoft.Data.Sqlite;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public class ProjectRepository : IProjectRepository
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public Project GetById(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Projects WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    using (var reader = cmd.ExecuteReader())
                    {
                        if (reader.Read()) return MapReaderToProject(reader);
                    }
                }
            }
            return null!;
        }

        public IEnumerable<Project> GetAll()
        {
            var list = new List<Project>();
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Projects ORDER BY Id DESC;";
                using (var cmd = new SqliteCommand(query, conn))
                using (var reader = cmd.ExecuteReader())
                {
                    while (reader.Read()) list.Add(MapReaderToProject(reader));
                }
            }
            return list;
        }

        public void Add(Project entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    INSERT INTO Projects (ClientName, PhoneNumber, CompanyName, ProjectTitle, Description, ContractAmount, ProjectCost, StartDate, DeliveryDate, Status, Priority, Notes, Files, InvoiceNumber, PaymentMethod, ProjectCategory, ProjectColorLabel, IsPinned, IsFavorite)
                    VALUES (@ClientName, @PhoneNumber, @CompanyName, @ProjectTitle, @Description, @ContractAmount, @ProjectCost, @StartDate, @DeliveryDate, @Status, @Priority, @Notes, @Files, @InvoiceNumber, @PaymentMethod, @ProjectCategory, @ProjectColorLabel, @IsPinned, @IsFavorite);
                    SELECT last_insert_rowid();";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    AddParameters(cmd, entity);
                    entity.Id = Convert.ToInt32(cmd.ExecuteScalar());
                }
            }
        }

        public void Update(Project entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    UPDATE Projects SET
                        ClientName = @ClientName, PhoneNumber = @PhoneNumber, CompanyName = @CompanyName, ProjectTitle = @ProjectTitle,
                        Description = @Description, ContractAmount = @ContractAmount, ProjectCost = @ProjectCost, StartDate = @StartDate,
                        DeliveryDate = @DeliveryDate, Status = @Status, Priority = @Priority, Notes = @Notes, Files = @Files,
                        InvoiceNumber = @InvoiceNumber, PaymentMethod = @PaymentMethod, ProjectCategory = @ProjectCategory,
                        ProjectColorLabel = @ProjectColorLabel, IsPinned = @IsPinned, IsFavorite = @IsFavorite
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
                string query = "DELETE FROM Projects WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        private Project MapReaderToProject(SqliteDataReader reader)
        {
            return new Project
            {
                Id = Convert.ToInt32(reader["Id"]),
                ClientName = reader["ClientName"]?.ToString() ?? "",
                PhoneNumber = reader["PhoneNumber"]?.ToString() ?? "",
                CompanyName = reader["CompanyName"]?.ToString() ?? "",
                ProjectTitle = reader["ProjectTitle"]?.ToString() ?? "",
                Description = reader["Description"]?.ToString() ?? "",
                ContractAmount = Convert.ToDecimal(reader["ContractAmount"]),
                ProjectCost = Convert.ToDecimal(reader["ProjectCost"]),
                StartDate = reader["StartDate"]?.ToString() ?? "",
                DeliveryDate = reader["DeliveryDate"]?.ToString() ?? "",
                Status = reader["Status"]?.ToString() ?? "در حال انجام",
                Priority = reader["Priority"]?.ToString() ?? "متوسط",
                Notes = reader["Notes"]?.ToString() ?? "",
                Files = reader["Files"]?.ToString() ?? "",
                InvoiceNumber = reader["InvoiceNumber"]?.ToString() ?? "",
                PaymentMethod = reader["PaymentMethod"]?.ToString() ?? "نقدی",
                ProjectCategory = reader["ProjectCategory"]?.ToString() ?? "طراحی سایت",
                ProjectColorLabel = reader["ProjectColorLabel"]?.ToString() ?? "#4A90E2",
                IsPinned = Convert.ToInt32(reader["IsPinned"]) == 1,
                IsFavorite = Convert.ToInt32(reader["IsFavorite"]) == 1
            };
        }

        private void AddParameters(SqliteCommand cmd, Project entity)
        {
            cmd.Parameters.AddWithValue("@ClientName", entity.ClientName);
            cmd.Parameters.AddWithValue("@PhoneNumber", entity.PhoneNumber);
            cmd.Parameters.AddWithValue("@CompanyName", entity.CompanyName);
            cmd.Parameters.AddWithValue("@ProjectTitle", entity.ProjectTitle);
            cmd.Parameters.AddWithValue("@Description", entity.Description);
            cmd.Parameters.AddWithValue("@ContractAmount", entity.ContractAmount);
            cmd.Parameters.AddWithValue("@ProjectCost", entity.ProjectCost);
            cmd.Parameters.AddWithValue("@StartDate", entity.StartDate);
            cmd.Parameters.AddWithValue("@DeliveryDate", entity.DeliveryDate);
            cmd.Parameters.AddWithValue("@Status", entity.Status);
            cmd.Parameters.AddWithValue("@Priority", entity.Priority);
            cmd.Parameters.AddWithValue("@Notes", entity.Notes);
            cmd.Parameters.AddWithValue("@Files", entity.Files);
            cmd.Parameters.AddWithValue("@InvoiceNumber", entity.InvoiceNumber);
            cmd.Parameters.AddWithValue("@PaymentMethod", entity.PaymentMethod);
            cmd.Parameters.AddWithValue("@ProjectCategory", entity.ProjectCategory);
            cmd.Parameters.AddWithValue("@ProjectColorLabel", entity.ProjectColorLabel);
            cmd.Parameters.AddWithValue("@IsPinned", entity.IsPinned ? 1 : 0);
            cmd.Parameters.AddWithValue("@IsFavorite", entity.IsFavorite ? 1 : 0);
        }
    }
}