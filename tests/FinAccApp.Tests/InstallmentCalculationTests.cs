using System;
using Xunit;
using FinAccApp.Models;

namespace FinAccApp.Tests
{
    public class InstallmentCalculationTests
    {
        [Fact]
        public void Test_ExactThreeInstallmentCalculation()
        {
            var inst = new Installment
            {
                Installment1Amount = 3000000,
                Installment1Paid = true,
                Installment2Amount = 3000000,
                Installment2Paid = false,
                Installment3Amount = 4000000,
                Installment3Paid = false
            };

            // Paid amount = 3000000
            // Total = 10,000,000
            // Remaining = 7,000,000
            // Pct = 30%
            Assert.Equal(10000000, inst.TotalAmount);
            Assert.Equal(3000000, inst.ReceivedAmount);
            Assert.Equal(7000000, inst.RemainingAmount);
            Assert.Equal(30.0, inst.PaymentPercentage);
        }

        [Fact]
        public void Test_FullyPaidPercentage()
        {
            var inst = new Installment
            {
                Installment1Amount = 1000,
                Installment1Paid = true,
                Installment2Amount = 1000,
                Installment2Paid = true,
                Installment3Amount = 1000,
                Installment3Paid = true
            };

            Assert.Equal(100.0, inst.PaymentPercentage);
            Assert.Equal(0, inst.RemainingAmount);
        }
    }
}