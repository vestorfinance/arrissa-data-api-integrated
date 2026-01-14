import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score, mean_absolute_error, r2_score
import tensorflow as tf
from tensorflow import keras
from tensorflow.keras import layers
import pickle

print("TensorFlow version:", tf.__version__)
print("\nLoading DXY training data...")
df = pd.read_csv('preprocessed_data/training-data-dxy.csv')

print(f"Total records: {len(df)}")
print(f"\nFirst few rows:")
print(df.head())

# Prepare features (X) - ONLY next week state of economy
X = df[['next_week_state_of_the_economy']].values

print(f"\n*** USING ONLY NEXT WEEK STATE OF ECONOMY ***")

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

print(f"\nTraining set size: {len(X_train)}")
print(f"Test set size: {len(X_test)}")

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

# Reshape for LSTM (samples, timesteps, features)
# We'll use each day's 1 economic indicator as a single timestep
X_train_lstm = X_train_scaled.reshape((X_train_scaled.shape[0], 1, X_train_scaled.shape[1]))
X_test_lstm = X_test_scaled.reshape((X_test_scaled.shape[0], 1, X_test_scaled.shape[1]))

print(f"\nLSTM input shape: {X_train_lstm.shape}")

print("\n" + "="*60)
print("TRAINING DIRECTION CLASSIFIER (LSTM)")
print("="*60)

# Build LSTM model for direction classification
direction_model = keras.Sequential([
    layers.LSTM(64, activation='tanh', return_sequences=True, input_shape=(1, 1)),
    layers.Dropout(0.2),
    layers.LSTM(32, activation='tanh'),
    layers.Dropout(0.2),
    layers.Dense(16, activation='relu'),
    layers.Dropout(0.1),
    layers.Dense(1, activation='sigmoid')
])

direction_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.001),
    loss='binary_crossentropy',
    metrics=['accuracy']
)

print("\nModel Architecture:")
direction_model.summary()

print("\nTraining model...")
history_dir = direction_model.fit(
    X_train_lstm, y_dir_train,
    epochs=50,
    batch_size=32,
    validation_split=0.2,
    verbose=1
)

# Predict on test set
y_dir_pred_prob = direction_model.predict(X_test_lstm, verbose=0)
y_dir_pred = (y_dir_pred_prob > 0.5).astype(int).flatten()

# Evaluate direction model
print("\n--- Direction Prediction Results ---")
print(f"Accuracy: {accuracy_score(y_dir_test, y_dir_pred)*100:.2f}%")
print("\nClassification Report:")
print(classification_report(y_dir_test, y_dir_pred, target_names=['DOWN', 'UP']))

print("\n" + "="*60)
print("TRAINING MAGNITUDE REGRESSOR (LSTM)")
print("="*60)

# Build LSTM model for magnitude regression
magnitude_model = keras.Sequential([
    layers.LSTM(64, activation='tanh', return_sequences=True, input_shape=(1, 1)),
    layers.Dropout(0.2),
    layers.LSTM(32, activation='tanh'),
    layers.Dropout(0.2),
    layers.Dense(16, activation='relu'),
    layers.Dropout(0.1),
    layers.Dense(1, activation='linear')
])

magnitude_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.001),
    loss='mean_absolute_error',
    metrics=['mae']
)

print("\nModel Architecture:")
magnitude_model.summary()

print("\nTraining model...")
history_mag = magnitude_model.fit(
    X_train_lstm, y_mag_train,
    epochs=50,
    batch_size=32,
    validation_split=0.2,
    verbose=1
)

# Predict on test set
y_mag_pred = magnitude_model.predict(X_test_lstm, verbose=0).flatten()

# Evaluate magnitude model
print("\n--- Magnitude Prediction Results ---")
mae = mean_absolute_error(y_mag_test, y_mag_pred)
r2 = r2_score(y_mag_test, y_mag_pred)
print(f"Mean Absolute Error: {mae:.4f}%")
print(f"R² Score: {r2:.4f}")

# Save models
print("\n" + "="*60)
print("SAVING MODELS")
print("="*60)

# Save Keras models
direction_model.save('lstm_direction_model.keras')
magnitude_model.save('lstm_magnitude_model.keras')

# Save scaler
with open('lstm_scaler.pkl', 'wb') as f:
    pickle.dump(scaler, f)

print("\nModels saved:")
print("  - lstm_direction_model.keras")
print("  - lstm_magnitude_model.keras")
print("  - lstm_scaler.pkl")

# Show some sample predictions
print("\n" + "="*60)
print("SAMPLE PREDICTIONS (First 10 from test set)")
print("="*60)
print("\n{:<6} {:<8} {:<8} {:<12} {:<12}".format("Index", "Actual", "Predicted", "Actual Mag", "Pred Mag"))
print("-" * 60)

for i in range(min(10, len(y_dir_test))):
    actual_dir = 'UP' if y_dir_test[i] == 1 else 'DOWN'
    pred_dir = 'UP' if y_dir_pred[i] == 1 else 'DOWN'
    actual_mag = y_mag_test[i]
    pred_mag = y_mag_pred[i]
    
    print("{:<6} {:<8} {:<8} {:<12.2f} {:<12.2f}".format(
        i, actual_dir, pred_dir, actual_mag, pred_mag
    ))

# Plot training history
print("\n" + "="*60)
print("TRAINING HISTORY")
print("="*60)

print("\nDirection Model - Final Training Accuracy: {:.2f}%".format(
    history_dir.history['accuracy'][-1] * 100
))
print("Direction Model - Final Validation Accuracy: {:.2f}%".format(
    history_dir.history['val_accuracy'][-1] * 100
))

print("\nMagnitude Model - Final Training MAE: {:.4f}".format(
    history_mag.history['mae'][-1]
))
print("Magnitude Model - Final Validation MAE: {:.4f}".format(
    history_mag.history['val_mae'][-1]
))

print("\n" + "="*60)
print("TRAINING COMPLETE!")
print("="*60)
