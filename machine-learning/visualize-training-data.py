import pandas as pd
import matplotlib.pyplot as plt
from matplotlib.dates import DateFormatter
import matplotlib.dates as mdates

# Read the training data
df = pd.read_csv('preprocessed_data/training-data.csv')

# Convert date to datetime
df['date'] = pd.to_datetime(df['date'])

# Sort by date
df = df.sort_values('date')

# Calculate cumulative values to show price-like movement
df['cumulative_today'] = df['today_state_of_the_economy'].cumsum()
df['cumulative_tomorrow'] = df['tomorrow_state_of_the_economy'].cumsum()
df['cumulative_next_week'] = df['next_week_state_of_the_economy'].cumsum()
df['cumulative_price'] = df['magnitude'].cumsum()

# Create figure with stacked subplots
fig, axes = plt.subplots(4, 1, figsize=(14, 12), sharex=True)
fig.suptitle('XAU/USD Training Data: Economic States & Price Movement (2014-2024)', fontsize=16, fontweight='bold')

# Plot 1: Today State of Economy (Cumulative)
axes[0].plot(df['date'], df['cumulative_today'], color='#2E86AB', linewidth=1.5)
axes[0].set_ylabel('Cumulative Value', fontweight='bold', fontsize=10)
axes[0].set_title('Today State of Economy (Cumulative)', fontweight='bold', pad=10)
axes[0].grid(True, alpha=0.2, linestyle='--')
axes[0].spines['top'].set_visible(False)
axes[0].spines['right'].set_visible(False)

# Plot 2: Tomorrow State of Economy (Cumulative)
axes[1].plot(df['date'], df['cumulative_tomorrow'], color='#F18F01', linewidth=1.5)
axes[1].set_ylabel('Cumulative Value', fontweight='bold', fontsize=10)
axes[1].set_title('Tomorrow State of Economy (Cumulative)', fontweight='bold', pad=10)
axes[1].grid(True, alpha=0.2, linestyle='--')
axes[1].spines['top'].set_visible(False)
axes[1].spines['right'].set_visible(False)

# Plot 3: Next Week State of Economy (Cumulative)
axes[2].plot(df['date'], df['cumulative_next_week'], color='#A23B72', linewidth=1.5)
axes[2].set_ylabel('Cumulative Value', fontweight='bold', fontsize=10)
axes[2].set_title('Next Week State of Economy (Cumulative)', fontweight='bold', pad=10)
axes[2].grid(True, alpha=0.2, linestyle='--')
axes[2].spines['top'].set_visible(False)
axes[2].spines['right'].set_visible(False)

# Plot 4: Price Movement (Cumulative)
axes[3].plot(df['date'], df['cumulative_price'], color='#C73E1D', linewidth=1.5)
axes[3].set_ylabel('Cumulative %', fontweight='bold', fontsize=10)
axes[3].set_xlabel('Date', fontweight='bold', fontsize=11)
axes[3].set_title('Price Movement - Cumulative Change %', fontweight='bold', pad=10)
axes[3].grid(True, alpha=0.2, linestyle='--')
axes[3].spines['top'].set_visible(False)
axes[3].spines['right'].set_visible(False)

# Format x-axis for all subplots
for ax in axes:
    ax.xaxis.set_major_formatter(DateFormatter('%Y-%m'))
    ax.xaxis.set_major_locator(mdates.YearLocator())
    ax.xaxis.set_minor_locator(mdates.MonthLocator((1, 7)))

# Rotate x-axis labels
plt.setp(axes[3].xaxis.get_majorticklabels(), rotation=45, ha='right')

# Adjust layout to prevent overlap
plt.tight_layout()

# Save the figure
output_file = 'preprocessed_data/training-data-visualization.png'
plt.savefig(output_file, dpi=150, bbox_inches='tight')
print(f"Visualization saved to: {output_file}")

# Display the plot
plt.show()
