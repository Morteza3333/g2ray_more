using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Media;
using System.Windows.Shapes;

namespace FinAccApp.Views.Controls
{
    public partial class GlassChart : UserControl
    {
        public GlassChart()
        {
            InitializeComponent();
            SizeChanged += (s, e) => RedrawChart();
        }

        public string Title
        {
            get => ChartTitle.Text;
            set => ChartTitle.Text = value;
        }

        private string _type = "Bar";
        public string Type
        {
            get => _type;
            set
            {
                _type = value;
                UpdateCanvasVisibility();
            }
        }

        private List<ChartItem> _items = new List<ChartItem>();
        public void SetData(List<ChartItem> items)
        {
            _items = items ?? new List<ChartItem>();
            UpdateCanvasVisibility();
            RedrawChart();
        }

        private void UpdateCanvasVisibility()
        {
            BarCanvas.Visibility = Visibility.Collapsed;
            PieGrid.Visibility = Visibility.Collapsed;
            LineCanvas.Visibility = Visibility.Collapsed;
            EmptyMsg.Visibility = Visibility.Collapsed;

            if (_items == null || _items.Count == 0)
            {
                EmptyMsg.Visibility = Visibility.Visible;
                return;
            }

            if (_type == "Bar") BarCanvas.Visibility = Visibility.Visible;
            else if (_type == "Pie") PieGrid.Visibility = Visibility.Visible;
            else if (_type == "Line") LineCanvas.Visibility = Visibility.Visible;
        }

        private void RedrawChart()
        {
            if (_items == null || _items.Count == 0) return;

            if (_type == "Bar") DrawBarChart();
            else if (_type == "Pie") DrawPieChart();
            else if (_type == "Line") DrawLineChart();
        }

        private void DrawBarChart()
        {
            BarCanvas.Children.Clear();
            double width = BarCanvas.ActualWidth;
            double height = BarCanvas.ActualHeight;
            if (width <= 0 || height <= 0) return;

            double maxVal = _items.Max(x => x.Value);
            if (maxVal <= 0) maxVal = 1;

            double paddingBottom = 30;
            double paddingTop = 15;
            double paddingLeft = 10;
            double paddingRight = 40;

            double chartHeight = height - paddingTop - paddingBottom;
            double chartWidth = width - paddingLeft - paddingRight;

            double barWidth = (chartWidth / _items.Count) * 0.6;
            double gap = (chartWidth / _items.Count) * 0.4;

            for (int i = 0; i <= 4; i++)
            {
                double y = paddingTop + (chartHeight * (4 - i) / 4);
                Line gridLine = new Line
                {
                    X1 = paddingLeft,
                    Y1 = y,
                    X2 = width - paddingRight,
                    Y2 = y,
                    Stroke = new SolidColorBrush(Color.FromArgb(40, 150, 150, 150)),
                    StrokeThickness = 1
                };
                BarCanvas.Children.Add(gridLine);

                TextBlock valLabel = new TextBlock
                {
                    Text = ((maxVal * i) / 4).ToString("N0"),
                    FontSize = 9,
                    Foreground = (Brush)FindResource("MutedTextBrush"),
                    FlowDirection = FlowDirection.LeftToRight
                };
                Canvas.SetLeft(valLabel, width - paddingRight + 5);
                Canvas.SetTop(valLabel, y - 6);
                BarCanvas.Children.Add(valLabel);
            }

            for (int i = 0; i < _items.Count; i++)
            {
                var item = _items[i];
                double barHeight = (item.Value / maxVal) * chartHeight;
                double x = paddingLeft + (i * (barWidth + gap)) + (gap / 2);
                double y = height - paddingBottom - barHeight;

                if (barHeight <= 0) barHeight = 2;

                Rectangle rect = new Rectangle
                {
                    Width = barWidth,
                    Height = barHeight,
                    RadiusX = 6,
                    RadiusY = 6,
                    Fill = string.IsNullOrEmpty(item.ColorHex) ? (Brush)FindResource("PrimaryBrush") : new SolidColorBrush((Color)ColorConverter.ConvertFromString(item.ColorHex))
                };
                Canvas.SetLeft(rect, x);
                Canvas.SetTop(rect, y);
                BarCanvas.Children.Add(rect);

                TextBlock label = new TextBlock
                {
                    Text = item.Label,
                    Width = barWidth + gap,
                    TextAlignment = TextAlignment.Center,
                    FontSize = 10,
                    Foreground = (Brush)FindResource("TextBrush")
                };
                Canvas.SetLeft(label, x - (gap / 2));
                Canvas.SetTop(label, height - paddingBottom + 5);
                BarCanvas.Children.Add(label);
            }
        }

        private void DrawPieChart()
        {
            PieCanvas.Children.Clear();
            LegendStack.Children.Clear();

            double width = PieCanvas.ActualWidth;
            double height = PieCanvas.ActualHeight;
            if (width <= 0 || height <= 0) return;

            double total = _items.Sum(x => x.Value);
            if (total <= 0) total = 1;

            double cx = width / 2;
            double cy = height / 2;
            double radius = Math.Min(cx, cy) * 0.8;

            double startAngle = 0;

            for (int i = 0; i < _items.Count; i++)
            {
                var item = _items[i];
                double sweepAngle = (item.Value / total) * 360;

                Brush brush = string.IsNullOrEmpty(item.ColorHex)
                    ? GetPastelColor(i)
                    : new SolidColorBrush((Color)ColorConverter.ConvertFromString(item.ColorHex));

                if (sweepAngle >= 360)
                {
                    Ellipse ellipse = new Ellipse
                    {
                        Width = radius * 2,
                        Height = radius * 2,
                        Fill = brush
                    };
                    Canvas.SetLeft(ellipse, cx - radius);
                    Canvas.SetTop(ellipse, cy - radius);
                    PieCanvas.Children.Add(ellipse);
                }
                else if (sweepAngle > 0)
                {
                    PathGeometry pathGeom = new PathGeometry();
                    PathFigure fig = new PathFigure { StartPoint = new Point(cx, cy), IsClosed = true };

                    double x1 = cx + radius * Math.Cos(startAngle * Math.PI / 180);
                    double y1 = cy + radius * Math.Sin(startAngle * Math.PI / 180);

                    double endAngle = startAngle + sweepAngle;
                    double x2 = cx + radius * Math.Cos(endAngle * Math.PI / 180);
                    double y2 = cy + radius * Math.Sin(endAngle * Math.PI / 180);

                    fig.Segments.Add(new LineSegment(new Point(x1, y1), true));
                    fig.Segments.Add(new ArcSegment(new Point(x2, y2), new Size(radius, radius), 0, sweepAngle > 180, SweepDirection.Clockwise, true));

                    pathGeom.Figures.Add(fig);

                    Path path = new Path
                    {
                        Fill = brush,
                        Data = pathGeom
                    };
                    PieCanvas.Children.Add(path);
                }

                startAngle += sweepAngle;

                var legendRow = new StackPanel { Orientation = Orientation.Horizontal, HorizontalAlignment = HorizontalAlignment.Right, Margin = new Thickness(0, 3, 0, 3) };
                var colorBox = new Border { Width = 12, Height = 12, CornerRadius = new CornerRadius(3), Background = brush, Margin = new Thickness(5, 0, 0, 0) };
                var textBlock = new TextBlock
                {
                    Text = $"{item.Label}: {(item.Value / total * 100):0.0}%",
                    FontSize = 10,
                    Foreground = (Brush)FindResource("TextBrush")
                };

                legendRow.Children.Add(textBlock);
                legendRow.Children.Add(colorBox);
                LegendStack.Children.Add(legendRow);
            }
        }

        private void DrawLineChart()
        {
            LineCanvas.Children.Clear();
            double width = LineCanvas.ActualWidth;
            double height = LineCanvas.ActualHeight;
            if (width <= 0 || height <= 0) return;

            double maxVal = _items.Max(x => x.Value);
            if (maxVal <= 0) maxVal = 1;

            double paddingBottom = 30;
            double paddingTop = 15;
            double paddingLeft = 10;
            double paddingRight = 40;

            double chartHeight = height - paddingTop - paddingBottom;
            double chartWidth = width - paddingLeft - paddingRight;

            double stepX = chartWidth / Math.Max(1, _items.Count - 1);

            for (int i = 0; i <= 4; i++)
            {
                double y = paddingTop + (chartHeight * (4 - i) / 4);
                Line gridLine = new Line
                {
                    X1 = paddingLeft,
                    Y1 = y,
                    X2 = width - paddingRight,
                    Y2 = y,
                    Stroke = new SolidColorBrush(Color.FromArgb(40, 150, 150, 150)),
                    StrokeThickness = 1
                };
                LineCanvas.Children.Add(gridLine);
            }

            PathGeometry pathGeom = new PathGeometry();
            PathFigure fig = new PathFigure();

            for (int i = 0; i < _items.Count; i++)
            {
                var item = _items[i];
                double x = paddingLeft + (i * stepX);
                double y = height - paddingBottom - ((item.Value / maxVal) * chartHeight);

                if (i == 0) fig.StartPoint = new Point(x, y);
                else fig.Segments.Add(new LineSegment(new Point(x, y), true));

                Ellipse node = new Ellipse
                {
                    Width = 8,
                    Height = 8,
                    Fill = (Brush)FindResource("AccentBrush"),
                    Stroke = Brushes.White,
                    StrokeThickness = 1.5
                };
                Canvas.SetLeft(node, x - 4);
                Canvas.SetTop(node, y - 4);

                TextBlock label = new TextBlock
                {
                    Text = item.Label,
                    Width = stepX + 10,
                    TextAlignment = TextAlignment.Center,
                    FontSize = 9,
                    Foreground = (Brush)FindResource("TextBrush")
                };
                Canvas.SetLeft(label, x - (stepX / 2) - 5);
                Canvas.SetTop(label, height - paddingBottom + 5);

                LineCanvas.Children.Add(node);
                LineCanvas.Children.Add(label);
            }

            fig.IsClosed = false;
            pathGeom.Figures.Add(fig);

            Path chartPath = new Path
            {
                Stroke = (Brush)FindResource("AccentBrush"),
                StrokeThickness = 3,
                Data = pathGeom
            };

            LineCanvas.Children.Insert(0, chartPath);
        }

        private Brush GetPastelColor(int index)
        {
            Color[] colors = new Color[]
            {
                (Color)ColorConverter.ConvertFromString("#4A90E2"),
                (Color)ColorConverter.ConvertFromString("#9B51E0"),
                (Color)ColorConverter.ConvertFromString("#2ECC71"),
                (Color)ColorConverter.ConvertFromString("#F1C40F"),
                (Color)ColorConverter.ConvertFromString("#E74C3C"),
                (Color)ColorConverter.ConvertFromString("#1ABC9C")
            };
            return new SolidColorBrush(colors[index % colors.Length]);
        }
    }

    public class ChartItem
    {
        public string Label { get; set; } = string.Empty;
        public double Value { get; set; }
        public string ColorHex { get; set; } = string.Empty;
    }
}