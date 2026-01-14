import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from tensorflow import keras
import pickle

# Load data
data = pd.read_csv('preprocessed_data/training-data.csv')
X = data[['next_week_state_of_the_economy']].values
y_direction = (data['direction'] == 'UP').astype(int).values

# Split data
X_train, X_test, y_train, y_test = train_test_split(
    X, y_direction, test_size=0.2, random_state=42, shuffle=False
)

# Scale
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)
X_test_lstm = X_test_scaled.reshape((X_test_scaled.shape[0], 1, 1))

# Load model
model = keras.models.load_model('xauusd_direction_model.keras')

# Get prediction probabilities
y_pred_proba = model.predict(X_test_lstm, verbose=0).flatten()

print("="*70)
print("FINE-GRAINED THRESHOLD OPTIMIZATION (0.37 - 0.54 range)")
print("="*70)

# Test very fine-grained thresholds in the actual prediction range
thresholds = np.arange(0.37, 0.55, 0.001)
results = []

for threshold in thresholds:
    y_pred = (y_pred_proba > threshold).astype(int)
    accuracy = np.mean(y_pred == y_test)
    up_pct = np.sum(y_pred == 1) / len(y_pred) * 100
    down_pct = np.sum(y_pred == 0) / len(y_pred) * 100
    
    # Calculate balance score (how close to 50/50)
    balance_score = 100 - abs(up_pct - 50)
    
    results.append({
        'threshold': threshold,
        'accuracy': accuracy,
        'up_pct': up_pct,
        'down_pct': down_pct,
        'balance_score': balance_score
    })

# Convert to DataFrame for easier analysis
df_results = pd.DataFrame(results)

# Find best by accuracy
best_accuracy_idx = df_results['accuracy'].idxmax()
best_accuracy_row = df_results.iloc[best_accuracy_idx]

# Find best balanced (closest to 50/50)
best_balance_idx = df_results['balance_score'].idxmax()
best_balance_row = df_results.iloc[best_balance_idx]

# Find sweet spot (good accuracy AND good balance)
df_results['combined_score'] = df_results['accuracy'] * 0.7 + (df_results['balance_score'] / 100) * 0.3
best_combined_idx = df_results['combined_score'].idxmax()
best_combined_row = df_results.iloc[best_combined_idx]

print(f"\n{'Strategy':<25} {'Threshold':<12} {'Accuracy':<12} {'UP%':<10} {'DOWN%':<10}")
print("-"*70)

print(f"{'Best Accuracy':<25} {best_accuracy_row['threshold']:.4f}       "
      f"{best_accuracy_row['accuracy']*100:.2f}%        "
      f"{best_accuracy_row['up_pct']:.1f}%      "
      f"{best_accuracy_row['down_pct']:.1f}%")

print(f"{'Best Balance':<25} {best_balance_row['threshold']:.4f}       "
      f"{best_balance_row['accuracy']*100:.2f}%        "
      f"{best_balance_row['up_pct']:.1f}%      "
      f"{best_balance_row['down_pct']:.1f}%")

print(f"{'Optimal Combined':<25} {best_combined_row['threshold']:.4f}       "
      f"{best_combined_row['accuracy']*100:.2f}%        "
      f"{best_combined_row['up_pct']:.1f}%      "
      f"{best_combined_row['down_pct']:.1f}%")

# Show top 10 by accuracy
print("\n" + "="*70)
print("TOP 10 THRESHOLDS BY ACCURACY")
print("="*70)
top_10 = df_results.nlargest(10, 'accuracy')
print(f"{'Threshold':<12} {'Accuracy':<12} {'UP%':<10} {'DOWN%':<10} {'Balance':<10}")
print("-"*70)
for _, row in top_10.iterrows():
    print(f"{row['threshold']:.4f}       {row['accuracy']*100:.2f}%        "
          f"{row['up_pct']:.1f}%      {row['down_pct']:.1f}%      {row['balance_score']:.1f}")

# Recommendation
print("\n" + "="*70)
print("RECOMMENDATION")
print("="*70)
optimal_threshold = best_combined_row['threshold']
y_pred_optimal = (y_pred_proba > optimal_threshold).astype(int)

print(f"\nOptimal threshold: {optimal_threshold:.4f}")
print(f"  Accuracy: {best_combined_row['accuracy']*100:.2f}%")
print(f"  Predicted UP: {np.sum(y_pred_optimal == 1)} ({best_combined_row['up_pct']:.1f}%)")
print(f"  Predicted DOWN: {np.sum(y_pred_optimal == 0)} ({best_combined_row['down_pct']:.1f}%)")
print(f"  Actual UP: {np.sum(y_test == 1)} ({np.sum(y_test == 1)/len(y_test)*100:.1f}%)")
print(f"  Actual DOWN: {np.sum(y_test == 0)} ({np.sum(y_test == 0)/len(y_test)*100:.1f}%)")

# Save optimal threshold
with open('xauusd_optimal_threshold.txt', 'w') as f:
    f.write(f"{optimal_threshold:.6f}")

print(f"\n✓ Optimal threshold saved to: xauusd_optimal_threshold.txt")
print("="*70)
