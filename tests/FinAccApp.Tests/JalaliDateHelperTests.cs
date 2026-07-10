using System;
using Xunit;
using FinAccApp.Helpers;

namespace FinAccApp.Tests
{
    public class JalaliDateHelperTests
    {
        [Fact]
        public void Test_ToShamsi_Conversion()
        {
            // Gregorian 2024-03-20 is Shamsi 1403/01/01 (or close based on Calendar, usually 1403/01/01)
            DateTime dt = new DateTime(2024, 3, 20);
            string shamsi = JalaliDateHelper.ToShamsi(dt);

            Assert.NotEmpty(shamsi);
            Assert.Contains("/", shamsi);
        }

        [Fact]
        public void Test_AddDays_Shamsi()
        {
            string start = "1403/01/01";
            string next = JalaliDateHelper.AddDays(start, 10);

            Assert.Equal("1403/01/11", next);
        }

        [Fact]
        public void Test_GetDaysDifference()
        {
            string d1 = "1403/01/01";
            string d2 = "1403/01/15";
            int diff = JalaliDateHelper.GetDaysDifference(d1, d2);

            Assert.Equal(14, diff);
        }
    }
}