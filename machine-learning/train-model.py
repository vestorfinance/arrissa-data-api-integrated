import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.ensemble import GradientBoostingClassifier, GradientBoostingRegressor
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score, mean_absolute_error, r2_score
import pickle

print("Loading training data...")
df = pd.read_csv('preprocessed_data/training-data.csv')

print(f"Total records: {len(df)}")
print(f"\nFirst few rows:")
print(df.head())

# Prepare features (X) - economic states
X = df[['today_state_of_the_economy', 'tomorrow_state_of_the_economy', 'next_week_state_of_the_economy']].values

# Prepare targets (y)
# For direction: Convert UP/DOWN to binary (1 for UP, 0 for DOWN)
df['direction_binary'] = df['direction'].apply(lambda x: 1 if x == 'UP' else 0)
y_direction = df['direction_binary'].values

# For magnitude: Use the actual magnitude value
y_magnitude = df['magnitude'].values

print(f"\nFeatures shape: {X.shape}")
print(f"Direction labels shape: {y_direction.shape}")
print(f"Magnitude labels shape: {y_magnitude.shape}")

# Check class distribution
print(f"\nDirection distribution:")
print(f"UP: {np.sum(y_direction == 1)} ({np.sum(y_direction == 1)/len(y_direction)*100:.2f}%)")
print(f"DOWN: {np.sum(y_direction == 0)} ({np.sum(y_direction == 0)/len(y_direction)*100:.2f}%)")

# Split data into train and test sets (80/20 split)
X_train, X_test, y_dir_train, y_dir_test, y_mag_train, y_mag_test = train_test_split(
    X, y_direction, y_magnitude, test_size=0.2, random_state=42, shuffle=False
)

print(f"\nTrain set size: {len(X_train)}")
print(f"Test set size: {len(X_test)}")

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

print("\n" + "="*60)
print("TRAINING DIRECTION CLASSIFIER (UP/DOWN) - GRADIENT BOOSTING")
print("="*60)

# Train Gradient Boosting Classifier for direction
clf_direction = GradientBoostingClassifier(
    n_estimators=200,
    learning_rate=0.1,
    max_depth=5,
    min_samples_split=5,
    min_samples_leaf=2,
    subsample=0.8,
    random_state=42
)

clf_direction.fit(X_train_scaled, y_dir_train)

# Predict on test set
y_dir_pred = clf_direction.predict(X_test_scaled)

# Evaluate direction classifier
accuracy = accuracy_score(y_dir_test, y_dir_pred)
print(f"\nDirection Classifier Accuracy: {accuracy:.4f} ({accuracy*100:.2f}%)")

print("\nClassification Report:")
print(classification_report(y_dir_test, y_dir_pred, target_names=['DOWN', 'UP']))

# Feature importance for direction
feature_importance_dir = clf_direction.feature_importances_
feature_names = ['Today State', 'Tomorrow State', 'Next Week State']
print("\nFeature Importance (Direction):")
for name, importance in zip(feature_names, feature_importance_dir):
    print(f"  {name}: {importance:.4f}")

print("\n" + "="*60)
print("TRAINING MAGNITUDE REGRESSOR - GRADIENT BOOSTING")
print("="*60)

# Train Gradient Boosting Regressor for magnitude
reg_magnitude = GradientBoostingRegressor(
    n_estimators=200,
    learning_rate=0.1,
    max_depth=5,
    min_samples_split=5,
    min_samples_leaf=2,
    subsample=0.8,
    random_state=42
)

reg_magnitude.fit(X_train_scaled, y_mag_train)

# Predict on test set
y_mag_pred = reg_magnitude.predict(X_test_scaled)

# Evaluate magnitude regressor
mae = mean_absolute_error(y_mag_test, y_mag_pred)
r2 = r2_score(y_mag_test, y_mag_pred)

print(f"\nMagnitude Regressor Performance:")
print(f"  Mean Absolute Error: {mae:.4f}%")
print(f"  R² Score: {r2:.4f}")

# Feature importance for magnitude
feature_importance_mag = reg_magnitude.feature_importances_
print("\nFeature Importance (Magnitude):")
for name, importance in zip(feature_names, feature_importance_mag):
    print(f"  {name}: {importance:.4f}")

print("\n" + "="*60)
print("SAVING MODELS")
print("="*60)

# Save models and scaler
models = {
    'direction_classifier': clf_direction,
    'magnitude_regressor': reg_magnitude,
    'scaler': scaler,
    'feature_names': feature_names
}

model_file = 'preprocessed_data/trained_model.pkl'
with open(model_file, 'wb') as f:
    pickle.dump(models, f)

print(f"\nModels saved to: {model_file}")

# Show some example predictions
print("\n" + "="*60)
print("SAMPLE PREDICTIONS (First 10 test samples)")
print("="*60)

for i in range(min(10, len(X_test))):
    actual_dir = 'UP' if y_dir_test[i] == 1 else 'DOWN'
    pred_dir = 'UP' if y_dir_pred[i] == 1 else 'DOWN'
    actual_mag = y_mag_test[i]
    pred_mag = y_mag_pred[i]
    
    match = "✓" if actual_dir == pred_dir else "✗"
    
    print(f"\nSample {i+1}:")
    print(f"  Features: Today={X_test[i][0]:.2f}%, Tomorrow={X_test[i][1]:.2f}%, NextWeek={X_test[i][2]:.2f}%")
    print(f"  Direction: Actual={actual_dir}, Predicted={pred_dir} {match}")
    print(f"  Magnitude: Actual={actual_mag:.2f}%, Predicted={pred_mag:.2f}% (Error: {abs(actual_mag-pred_mag):.2f}%)")

print("\n" + "="*60)
print("TRAINING COMPLETE!")
print("="*60)
